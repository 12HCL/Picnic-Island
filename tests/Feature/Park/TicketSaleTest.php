<?php

namespace Tests\Feature\Park;

use App\Models\ParkActivity;
use App\Models\ParkEvent;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Park\TicketSalesService;
use Database\Seeders\RoleSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Module 4 — the two sales channels and BR-06.
 *
 * BR-06: park_events.seats_taken may not exceed park_events.capacity.
 * MASTER_SCHEMA.md §14: a tickets row is written only at payment confirmation, so the
 * ticket, the seat increment and the payments row are one atomic unit.
 */
class TicketSaleTest extends TestCase
{
    use RefreshDatabase;

    private User $visitor;
    private User $staff;
    private ParkActivity $activity;
    private ParkEvent $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->visitor = User::factory()->role('visitor')->create();
        $this->staff = User::factory()->role('park_staff')->create();

        $this->activity = ParkActivity::create([
            'name' => 'Sunset Coaster',
            'type' => 'ride',
            'default_capacity' => 40,
            'base_price' => 25.00,
            'is_active' => true,
        ]);

        $this->event = ParkEvent::create([
            'park_activity_id' => $this->activity->id,
            'event_date' => now()->addDays(3)->toDateString(),
            'start_time' => '17:30',
            'capacity' => 10,
            'price' => 25.00,
            'status' => 'scheduled',
        ]);
    }

    // ── Online sales ──────────────────────────────────────────────────────────

    public function test_a_visitor_can_buy_admissions_online(): void
    {
        $this->actingAs($this->visitor)->post(route('park.tickets.store'), [
            'park_event_id' => $this->event->id,
            'quantity' => 2,
            'method' => 'card',
        ])->assertRedirect();

        $ticket = Ticket::firstOrFail();

        $this->assertSame('online', $ticket->channel);
        $this->assertSame('valid', $ticket->status);
        $this->assertSame($this->visitor->id, $ticket->user_id);
        $this->assertNull($ticket->sold_by);
        $this->assertSame(2, $ticket->quantity);
        $this->assertStringStartsWith('PIB-TK-', $ticket->reference);

        // The seat counter moved with the sale.
        $this->assertSame(2, $this->event->fresh()->seats_taken);
    }

    /**
     * The payment is written in the same transaction as the ticket, against the ticket's
     * own id — BR-03 allows a payments row exactly one target.
     */
    public function test_a_sale_writes_a_matching_payment(): void
    {
        $this->actingAs($this->visitor)->post(route('park.tickets.store'), [
            'park_event_id' => $this->event->id,
            'quantity' => 3,
            'method' => 'transfer',
        ]);

        $ticket = Ticket::firstOrFail();

        $this->assertDatabaseHas('payments', [
            'ticket_id' => $ticket->id,
            'hotel_booking_id' => null,
            'ferry_ticket_id' => null,
            'user_id' => $this->visitor->id,
            'amount' => '75.00',
            'method' => 'transfer',
            'status' => 'paid',
        ]);
    }

    public function test_a_guest_cannot_buy(): void
    {
        $this->post(route('park.tickets.store'), [
            'park_event_id' => $this->event->id,
            'quantity' => 1,
            'method' => 'card',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_an_unknown_payment_method_is_rejected(): void
    {
        $this->actingAs($this->visitor)->post(route('park.tickets.store'), [
            'park_event_id' => $this->event->id,
            'quantity' => 1,
            'method' => 'crypto',
        ])->assertSessionHasErrors('method');

        $this->assertDatabaseCount('tickets', 0);
    }

    // ── BR-06 ─────────────────────────────────────────────────────────────────

    public function test_a_sale_beyond_capacity_is_refused(): void
    {
        $this->event->update(['seats_taken' => 9]); // capacity 10

        $this->actingAs($this->visitor)->post(route('park.tickets.store'), [
            'park_event_id' => $this->event->id,
            'quantity' => 2,
            'method' => 'card',
        ])->assertRedirect(route('park.events.show', $this->event));

        $this->assertDatabaseCount('tickets', 0);
        $this->assertSame(9, $this->event->fresh()->seats_taken);
    }

    public function test_the_last_seat_can_still_be_sold(): void
    {
        $this->event->update(['seats_taken' => 9]);

        $this->actingAs($this->visitor)->post(route('park.tickets.store'), [
            'park_event_id' => $this->event->id,
            'quantity' => 1,
            'method' => 'card',
        ]);

        $this->assertDatabaseCount('tickets', 1);
        $this->assertSame(10, $this->event->fresh()->seats_taken);
    }

    /**
     * BR-06 is a check-then-act race, so the refusal has to come from the service under its
     * row lock — not from a form request or a hidden button. Selling the event out and then
     * calling the service directly is the closest a single-threaded test suite gets to the
     * second request in that race.
     */
    public function test_the_service_itself_refuses_an_oversell(): void
    {
        $this->event->update(['seats_taken' => 10]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('sold out');

        app(TicketSalesService::class)->sell(
            event: $this->event,
            quantity: 1,
            channel: 'online',
            buyer: $this->visitor,
            seller: null,
            method: 'card',
        );
    }

    public function test_nothing_is_written_when_a_sale_is_refused(): void
    {
        $this->event->update(['seats_taken' => 10]);

        try {
            app(TicketSalesService::class)->sell(
                event: $this->event,
                quantity: 1,
                channel: 'online',
                buyer: $this->visitor,
                seller: null,
                method: 'card',
            );
        } catch (DomainException) {
            // expected
        }

        $this->assertDatabaseCount('tickets', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_a_cancelled_event_cannot_be_sold(): void
    {
        $this->event->update(['status' => 'cancelled']);

        $this->actingAs($this->visitor)->post(route('park.tickets.store'), [
            'park_event_id' => $this->event->id,
            'quantity' => 1,
            'method' => 'card',
        ])->assertRedirect();

        $this->assertDatabaseCount('tickets', 0);
    }

    // ── Gate sales ────────────────────────────────────────────────────────────

    public function test_park_staff_can_sell_at_the_gate_to_someone_with_no_account(): void
    {
        $this->event->update(['event_date' => now()->toDateString()]);

        $this->actingAs($this->staff)->post(route('park.staff.gate-sale.store'), [
            'park_event_id' => $this->event->id,
            'quantity' => 4,
            'method' => 'cash',
        ])->assertRedirect();

        $ticket = Ticket::firstOrFail();

        $this->assertSame('gate', $ticket->channel);
        // The buyer is anonymous by design; the seller is recorded instead.
        $this->assertNull($ticket->user_id);
        $this->assertSame($this->staff->id, $ticket->sold_by);
    }

    /**
     * The seller must never end up in payments.user_id — that column means the buyer, and
     * revenue is reported by channel precisely because a gate sale has no buyer.
     */
    public function test_a_gate_sale_leaves_the_payment_user_null(): void
    {
        $this->event->update(['event_date' => now()->toDateString()]);

        $this->actingAs($this->staff)->post(route('park.staff.gate-sale.store'), [
            'park_event_id' => $this->event->id,
            'quantity' => 1,
            'method' => 'cash',
        ]);

        $this->assertDatabaseHas('payments', [
            'ticket_id' => Ticket::firstOrFail()->id,
            'user_id' => null,
        ]);
    }

    public function test_a_visitor_cannot_reach_the_till(): void
    {
        $this->actingAs($this->visitor)
            ->get(route('park.staff.gate-sale'))
            ->assertForbidden();
    }

    public function test_the_till_refuses_an_oversell_too(): void
    {
        $this->event->update(['event_date' => now()->toDateString(), 'seats_taken' => 10]);

        $this->actingAs($this->staff)->post(route('park.staff.gate-sale.store'), [
            'park_event_id' => $this->event->id,
            'quantity' => 1,
            'method' => 'cash',
        ]);

        $this->assertDatabaseCount('tickets', 0);
    }

    // ── Reading and voiding a ticket ──────────────────────────────────────────

    public function test_a_visitor_cannot_read_someone_elses_ticket(): void
    {
        $other = User::factory()->role('visitor')->create();
        $ticket = $this->sellTo($this->visitor);

        $this->actingAs($other)
            ->get(route('park.tickets.show', $ticket))
            ->assertForbidden();
    }

    public function test_park_staff_can_read_any_ticket(): void
    {
        $ticket = $this->sellTo($this->visitor);

        $this->actingAs($this->staff)
            ->get(route('park.tickets.show', $ticket))
            ->assertOk()
            ->assertSee($ticket->reference);
    }

    public function test_the_buyer_can_read_their_own_ticket(): void
    {
        $ticket = $this->sellTo($this->visitor);

        $this->actingAs($this->visitor)
            ->get(route('park.tickets.show', $ticket))
            ->assertOk()
            ->assertSee($ticket->reference);
    }

    public function test_cancelling_returns_the_seats(): void
    {
        $ticket = $this->sellTo($this->visitor, 3);
        $this->assertSame(3, $this->event->fresh()->seats_taken);

        $this->actingAs($this->visitor)
            ->post(route('park.tickets.cancel', $ticket))
            ->assertRedirect();

        $this->assertSame('cancelled', $ticket->fresh()->status);
        $this->assertSame(0, $this->event->fresh()->seats_taken);

        // Voided, never deleted — the sales reports still see it.
        $this->assertDatabaseCount('tickets', 1);
    }

    public function test_a_ticket_cannot_be_cancelled_twice(): void
    {
        $ticket = $this->sellTo($this->visitor, 2);

        $this->actingAs($this->visitor)->post(route('park.tickets.cancel', $ticket));
        $this->actingAs($this->visitor)->post(route('park.tickets.cancel', $ticket));

        // The second attempt is refused, so the seats are returned once and not twice.
        $this->assertSame(0, $this->event->fresh()->seats_taken);
    }

    private function sellTo(User $buyer, int $quantity = 1): Ticket
    {
        return app(TicketSalesService::class)->sell(
            event: $this->event,
            quantity: $quantity,
            channel: 'online',
            buyer: $buyer,
            seller: null,
            method: 'card',
        );
    }
}
