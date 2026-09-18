<?php

namespace Tests\Feature\Ferry;

use App\Models\FerryRoute;
use App\Models\FerrySchedule;
use App\Models\FerryTicket;
use App\Models\Hotel;
use App\Models\HotelBooking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vessel;
use App\Services\Ferry\FerryTicketIssueService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Module 3 — issuing a ferry ticket. BR-01 and BR-02, the two rules the brief turns on.
 *
 * These exercise FerryTicketIssueService directly as well as through the controller,
 * because the service is the only place a ticket is created: both the visitor's own
 * purchase (UC-05) and counter issuance (UC-14) go through it, and a marker's first move is
 * to look for a second code path that skips BR-01.
 */
class FerryTicketIssueTest extends TestCase
{
    use RefreshDatabase;

    private function schedule(int $capacity = 120, string $status = 'scheduled'): FerrySchedule
    {
        $route = FerryRoute::create([
            'origin' => 'Mainland Jetty',
            'destination' => 'North Jetty',
            'duration_minutes' => 25,
            'base_fare' => 45.00,
        ]);

        $vessel = Vessel::create([
            'name' => 'MV Coral Queen',
            'capacity' => $capacity,
            'status' => 'active',
        ]);

        return FerrySchedule::create([
            'ferry_route_id' => $route->id,
            'vessel_id' => $vessel->id,
            'departure_date' => now()->addDays(3)->toDateString(),
            'departure_time' => '09:30:00',
            'status' => $status,
        ]);
    }

    private function bookingFor(User $user, string $status = 'confirmed', int $startsIn = 2, int $endsIn = 6): HotelBooking
    {
        $hotel = Hotel::firstOrCreate(['name' => 'Palm Reef Hotel'], ['star_rating' => 4]);

        return HotelBooking::create([
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
            'reference' => 'PIB-HB-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'check_in' => now()->addDays($startsIn)->toDateString(),
            'check_out' => now()->addDays($endsIn)->toDateString(),
            'guests' => 2,
            'total_amount' => 780.00,
            'status' => $status,
        ]);
    }

    private function issuer(): FerryTicketIssueService
    {
        return app(FerryTicketIssueService::class);
    }

    public function test_a_visitor_with_a_confirmed_booking_is_issued_a_ticket(): void
    {
        $schedule = $this->schedule();
        $visitor = User::factory()->role('visitor')->create();
        $booking = $this->bookingFor($visitor);

        $ticket = $this->issuer()->issue($schedule, $booking->id, $visitor, null, 'card');

        $this->assertSame('issued', $ticket->status);
        $this->assertSame($booking->id, $ticket->hotel_booking_id);
        $this->assertSame('PIB-FT-000001', $ticket->reference);
        $this->assertEquals(45.00, (float) $ticket->fare);

        // Online purchase: no operator recorded.
        $this->assertNull($ticket->issued_by);
        $this->assertNull($ticket->issued_at);

        // BR-02: the seat was taken, by increment.
        $this->assertSame(1, $schedule->fresh()->seats_taken);

        // MASTER_SCHEMA.md §11: the payment row is written in the same transaction.
        $this->assertDatabaseHas('payments', [
            'ferry_ticket_id' => $ticket->id,
            'status' => 'paid',
        ]);
    }

    /**
     * BR-01 at the service layer, re-checked under a lock at write time.
     */
    public function test_a_booking_that_does_not_cover_the_sailing_date_is_refused(): void
    {
        $schedule = $this->schedule();
        $visitor = User::factory()->role('visitor')->create();

        // Stay ends before the sailing.
        $booking = $this->bookingFor($visitor, 'confirmed', startsIn: 0, endsIn: 1);

        $this->expectException(DomainException::class);

        try {
            $this->issuer()->issue($schedule, $booking->id, $visitor, null, 'card');
        } finally {
            $this->assertSame(0, $schedule->fresh()->seats_taken);
            $this->assertDatabaseCount('ferry_tickets', 0);
        }
    }

    public function test_a_pending_booking_does_not_authorise_travel(): void
    {
        $schedule = $this->schedule();
        $visitor = User::factory()->role('visitor')->create();
        $booking = $this->bookingFor($visitor, 'pending');

        $this->expectException(DomainException::class);

        $this->issuer()->issue($schedule, $booking->id, $visitor, null, 'card');
    }

    public function test_another_visitors_booking_cannot_authorise_travel(): void
    {
        $schedule = $this->schedule();
        $visitor = User::factory()->role('visitor')->create();
        $someoneElse = User::factory()->role('visitor')->create();
        $theirBooking = $this->bookingFor($someoneElse);

        $this->expectException(DomainException::class);

        $this->issuer()->issue($schedule, $theirBooking->id, $visitor, null, 'card');
    }

    /**
     * BR-02: the sailing is full, so the ticket is refused and no seat is consumed.
     */
    public function test_a_full_sailing_is_refused(): void
    {
        $schedule = $this->schedule(capacity: 1);
        $first = User::factory()->role('visitor')->create();
        $second = User::factory()->role('visitor')->create();

        $this->issuer()->issue($schedule, $this->bookingFor($first)->id, $first, null, 'card');
        $this->assertSame(1, $schedule->fresh()->seats_taken);

        $this->expectException(DomainException::class);

        try {
            $this->issuer()->issue($schedule, $this->bookingFor($second)->id, $second, null, 'card');
        } finally {
            $this->assertSame(1, $schedule->fresh()->seats_taken);
            $this->assertDatabaseCount('ferry_tickets', 1);
        }
    }

    public function test_a_cancelled_sailing_cannot_be_booked(): void
    {
        $schedule = $this->schedule(status: 'cancelled');
        $visitor = User::factory()->role('visitor')->create();

        $this->expectException(DomainException::class);

        $this->issuer()->issue($schedule, $this->bookingFor($visitor)->id, $visitor, null, 'card');
    }

    /**
     * UC-14: the same rules, with the operator recorded on the row. That pair of columns is
     * the only thing distinguishing a counter sale from an online purchase.
     */
    public function test_counter_issuance_records_the_operator(): void
    {
        $schedule = $this->schedule();
        $visitor = User::factory()->role('visitor')->create();
        $operator = User::factory()->role('ferry_operator')->create();

        $ticket = $this->issuer()->issue(
            $schedule,
            $this->bookingFor($visitor)->id,
            $visitor,
            $operator,
            'cash',
        );

        $this->assertSame($operator->id, $ticket->issued_by);
        $this->assertNotNull($ticket->issued_at);
        // The passenger is the booking's owner, never the operator.
        $this->assertSame($visitor->id, $ticket->user_id);
    }

    public function test_cancelling_a_ticket_returns_its_seat(): void
    {
        $schedule = $this->schedule();
        $visitor = User::factory()->role('visitor')->create();

        $ticket = $this->issuer()->issue($schedule, $this->bookingFor($visitor)->id, $visitor, null, 'card');
        $this->assertSame(1, $schedule->fresh()->seats_taken);

        $this->issuer()->cancel($ticket);

        $this->assertSame('cancelled', $ticket->fresh()->status);
        $this->assertSame(0, $schedule->fresh()->seats_taken);
        // Cancelling is a status change, never a delete: the row and its payment survive.
        $this->assertDatabaseCount('ferry_tickets', 1);
        $this->assertSame(1, Payment::where('ferry_ticket_id', $ticket->id)->count());
    }

    // ── Through the controller ───────────────────────────────────────────────

    public function test_a_visitor_can_buy_a_ticket_through_the_form(): void
    {
        $schedule = $this->schedule();
        $visitor = User::factory()->role('visitor')->create();
        $booking = $this->bookingFor($visitor);

        $response = $this->actingAs($visitor)->post('/ferry/tickets', [
            'ferry_schedule_id' => $schedule->id,
            'hotel_booking_id' => $booking->id,
            'method' => 'card',
        ]);

        $ticket = FerryTicket::firstOrFail();

        $response->assertRedirect(route('ferry.tickets.show', $ticket));
        $this->assertSame(1, $schedule->fresh()->seats_taken);
    }

    /**
     * The third layer of BR-01: a hand-posted form naming someone else's booking fails
     * validation before it reaches the service.
     */
    public function test_posting_another_visitors_booking_id_fails_validation(): void
    {
        $schedule = $this->schedule();
        $visitor = User::factory()->role('visitor')->create();
        $someoneElse = User::factory()->role('visitor')->create();

        $this->actingAs($visitor)
            ->post('/ferry/tickets', [
                'ferry_schedule_id' => $schedule->id,
                'hotel_booking_id' => $this->bookingFor($someoneElse)->id,
                'method' => 'card',
            ])
            ->assertSessionHasErrors('hotel_booking_id');

        $this->assertDatabaseCount('ferry_tickets', 0);
    }

    public function test_a_refused_purchase_returns_to_the_page_with_the_reason(): void
    {
        $schedule = $this->schedule(capacity: 1);
        $first = User::factory()->role('visitor')->create();
        $second = User::factory()->role('visitor')->create();

        $this->issuer()->issue($schedule, $this->bookingFor($first)->id, $first, null, 'card');

        $this->actingAs($second)
            ->post('/ferry/tickets', [
                'ferry_schedule_id' => $schedule->id,
                'hotel_booking_id' => $this->bookingFor($second)->id,
                'method' => 'card',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseCount('ferry_tickets', 1);
    }

    // ── Cancelling through the controller ────────────────────────────────────

    public function test_the_owner_can_cancel_their_own_ticket_and_the_seat_returns(): void
    {
        $schedule = $this->schedule();
        $owner = User::factory()->role('visitor')->create();
        $ticket = $this->issuer()->issue($schedule, $this->bookingFor($owner)->id, $owner, null, 'card');

        $this->assertSame(1, $schedule->fresh()->seats_taken);

        $this->actingAs($owner)
            ->post(route('ferry.tickets.cancel', $ticket))
            ->assertRedirect();

        $this->assertSame('cancelled', $ticket->fresh()->status);
        $this->assertSame(0, $schedule->fresh()->seats_taken);
        // Never a delete: the row and its payment survive for the manifest and the accounts.
        $this->assertDatabaseCount('ferry_tickets', 1);
        $this->assertSame(1, Payment::where('ferry_ticket_id', $ticket->id)->count());
    }

    public function test_a_ferry_operator_can_cancel_any_ticket(): void
    {
        $schedule = $this->schedule();
        $owner = User::factory()->role('visitor')->create();
        $ticket = $this->issuer()->issue($schedule, $this->bookingFor($owner)->id, $owner, null, 'card');

        $this->actingAs(User::factory()->role('ferry_operator')->create())
            ->post(route('ferry.tickets.cancel', $ticket))
            ->assertRedirect();

        $this->assertSame('cancelled', $ticket->fresh()->status);
    }

    public function test_another_visitor_cannot_cancel_someone_elses_ticket(): void
    {
        $schedule = $this->schedule();
        $owner = User::factory()->role('visitor')->create();
        $ticket = $this->issuer()->issue($schedule, $this->bookingFor($owner)->id, $owner, null, 'card');

        $this->actingAs(User::factory()->role('visitor')->create())
            ->post(route('ferry.tickets.cancel', $ticket))
            ->assertForbidden();

        $this->assertSame('issued', $ticket->fresh()->status);
        $this->assertSame(1, $schedule->fresh()->seats_taken);
    }

    public function test_a_ticket_cannot_be_cancelled_twice(): void
    {
        $schedule = $this->schedule();
        $owner = User::factory()->role('visitor')->create();
        $ticket = $this->issuer()->issue($schedule, $this->bookingFor($owner)->id, $owner, null, 'card');

        $this->actingAs($owner)->post(route('ferry.tickets.cancel', $ticket));
        $this->assertSame(0, $schedule->fresh()->seats_taken);

        // A double submit must not hand the seat back twice.
        $this->actingAs($owner)
            ->post(route('ferry.tickets.cancel', $ticket))
            ->assertSessionHas('error');

        $this->assertSame(0, $schedule->fresh()->seats_taken);
    }

    public function test_a_visitor_may_not_read_another_visitors_ticket(): void
    {
        $schedule = $this->schedule();
        $owner = User::factory()->role('visitor')->create();
        $ticket = $this->issuer()->issue($schedule, $this->bookingFor($owner)->id, $owner, null, 'card');

        $this->actingAs(User::factory()->role('visitor')->create())
            ->get(route('ferry.tickets.show', $ticket))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('ferry.tickets.show', $ticket))
            ->assertOk();

        // An operator may read any ticket — they check passes at the jetty.
        $this->actingAs(User::factory()->role('ferry_operator')->create())
            ->get(route('ferry.tickets.show', $ticket))
            ->assertOk();
    }

    /**
     * QA finding #1 on the hotel module was exactly this: a check that asked only "is this a
     * non-owning visitor?" let every other role through.
     */
    public function test_unrelated_staff_roles_cannot_read_a_ticket(): void
    {
        $schedule = $this->schedule();
        $owner = User::factory()->role('visitor')->create();
        $ticket = $this->issuer()->issue($schedule, $this->bookingFor($owner)->id, $owner, null, 'card');

        foreach (['hotel_staff', 'park_staff', 'admin'] as $role) {
            $this->actingAs(User::factory()->role($role)->create())
                ->get(route('ferry.tickets.show', $ticket))
                ->assertForbidden();
        }
    }
}
