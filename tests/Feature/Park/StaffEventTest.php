<?php

namespace Tests\Feature\Park;

use App\Models\ParkActivity;
use App\Models\ParkEvent;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Park\TicketSalesService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Scheduling — the staff half of park_events. MASTER_SCHEMA.md §13.
 */
class StaffEventTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $visitor;
    private ParkActivity $activity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->staff = User::factory()->role('park_staff')->create();
        $this->visitor = User::factory()->role('visitor')->create();

        $this->activity = ParkActivity::create([
            'name' => 'Sunset Coaster',
            'type' => 'ride',
            'default_capacity' => 40,
            'base_price' => 25.00,
        ]);
    }

    // ── Access ────────────────────────────────────────────────────────────────

    public function test_a_visitor_cannot_reach_the_schedule(): void
    {
        $this->actingAs($this->visitor)
            ->get(route('park.staff.events.index'))
            ->assertForbidden();
    }

    public function test_park_staff_can_see_the_schedule(): void
    {
        $this->event();

        $this->actingAs($this->staff)
            ->get(route('park.staff.events.index'))
            ->assertOk()
            ->assertSee('Sunset Coaster');
    }

    // ── Scheduling ────────────────────────────────────────────────────────────

    public function test_park_staff_can_schedule_an_event(): void
    {
        $date = now()->addDays(4)->toDateString();

        $this->actingAs($this->staff)->post(route('park.staff.events.store'), [
            'park_activity_id' => $this->activity->id,
            'event_date' => $date,
            'start_time' => '17:30',
            'capacity' => 40,
            'price' => 25.00,
        ])->assertRedirect();

        $this->assertDatabaseHas('park_events', [
            'park_activity_id' => $this->activity->id,
            'capacity' => 40,
            'seats_taken' => 0,
            'status' => 'scheduled',
        ]);
    }

    public function test_an_event_cannot_be_scheduled_in_the_past(): void
    {
        $this->actingAs($this->staff)->post(route('park.staff.events.store'), [
            'park_activity_id' => $this->activity->id,
            'event_date' => now()->subDay()->toDateString(),
            'start_time' => '17:30',
            'capacity' => 40,
            'price' => 25.00,
        ])->assertSessionHasErrors('event_date');

        $this->assertDatabaseCount('park_events', 0);
    }

    /**
     * The UNIQUE index on (park_activity_id, event_date, start_time) would otherwise
     * surface as a raw SQLSTATE 23000 on a white screen.
     */
    public function test_the_same_activity_cannot_run_twice_at_one_time(): void
    {
        $event = $this->event();

        $this->actingAs($this->staff)->post(route('park.staff.events.store'), [
            'park_activity_id' => $this->activity->id,
            'event_date' => $event->event_date->toDateString(),
            'start_time' => '17:30',
            'capacity' => 40,
            'price' => 25.00,
        ])->assertSessionHasErrors('start_time');

        $this->assertDatabaseCount('park_events', 1);
    }

    public function test_two_different_activities_can_run_at_the_same_time(): void
    {
        $event = $this->event();

        $other = ParkActivity::create([
            'name' => 'Dolphin Show',
            'type' => 'show',
            'default_capacity' => 100,
            'base_price' => 15.00,
        ]);

        $this->actingAs($this->staff)->post(route('park.staff.events.store'), [
            'park_activity_id' => $other->id,
            'event_date' => $event->event_date->toDateString(),
            'start_time' => '17:30',
            'capacity' => 100,
            'price' => 15.00,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('park_events', 2);
    }

    // ── Editing ───────────────────────────────────────────────────────────────

    public function test_park_staff_can_change_capacity_and_price(): void
    {
        $event = $this->event();

        $this->actingAs($this->staff)->put(route('park.staff.events.update', $event), [
            'park_activity_id' => $this->activity->id,
            'event_date' => $event->event_date->toDateString(),
            'start_time' => '17:30',
            'capacity' => 60,
            'price' => 30.00,
            'status' => 'scheduled',
        ])->assertRedirect();

        $this->assertDatabaseHas('park_events', ['id' => $event->id, 'capacity' => 60]);
    }

    /**
     * Lowering capacity below seats_taken would create exactly the state BR-06 says cannot
     * exist, after the fact and with no way to un-sell the tickets.
     */
    public function test_capacity_cannot_be_lowered_below_what_is_already_sold(): void
    {
        $event = $this->event();
        $this->sell($event, 8);

        $this->actingAs($this->staff)->put(route('park.staff.events.update', $event), [
            'park_activity_id' => $this->activity->id,
            'event_date' => $event->event_date->toDateString(),
            'start_time' => '17:30',
            'capacity' => 5,
            'price' => 25.00,
            'status' => 'scheduled',
        ])->assertSessionHasErrors('capacity');

        $this->assertSame(20, $event->fresh()->capacity);
    }

    /**
     * Repricing an event must not rewrite what past buyers were charged — tickets carry
     * their own unit_price for this reason.
     */
    public function test_repricing_does_not_change_tickets_already_sold(): void
    {
        $event = $this->event();
        $ticket = $this->sell($event, 2);

        $this->actingAs($this->staff)->put(route('park.staff.events.update', $event), [
            'park_activity_id' => $this->activity->id,
            'event_date' => $event->event_date->toDateString(),
            'start_time' => '17:30',
            'capacity' => 20,
            'price' => 99.00,
            'status' => 'scheduled',
        ]);

        $this->assertSame('25.00', $ticket->fresh()->unit_price);
    }

    // ── Cancelling and deleting ───────────────────────────────────────────────

    public function test_cancelling_an_event_keeps_it_and_its_tickets(): void
    {
        $event = $this->event();
        $this->sell($event, 3);

        $this->actingAs($this->staff)
            ->post(route('park.staff.events.cancel', $event))
            ->assertRedirect();

        $this->assertSame('cancelled', $event->fresh()->status);
        $this->assertDatabaseCount('tickets', 1);
    }

    public function test_an_event_with_no_tickets_can_be_deleted(): void
    {
        $event = $this->event();

        $this->actingAs($this->staff)
            ->delete(route('park.staff.events.destroy', $event))
            ->assertRedirect();

        $this->assertDatabaseCount('park_events', 0);
    }

    /**
     * tickets.park_event_id is restrictOnDelete, so the sales history cannot be orphaned.
     * The controller turns the database refusal into a message pointing at cancellation.
     */
    public function test_an_event_with_tickets_cannot_be_deleted(): void
    {
        $event = $this->event();
        $this->sell($event, 1);

        $this->actingAs($this->staff)->delete(route('park.staff.events.destroy', $event));

        $this->assertDatabaseCount('park_events', 1);
        $this->assertDatabaseCount('tickets', 1);
    }

    // ── The link to the visitor side ──────────────────────────────────────────

    public function test_a_newly_scheduled_event_appears_to_visitors(): void
    {
        $date = now()->addDays(4)->toDateString();

        $this->actingAs($this->staff)->post(route('park.staff.events.store'), [
            'park_activity_id' => $this->activity->id,
            'event_date' => $date,
            'start_time' => '17:30',
            'capacity' => 40,
            'price' => 25.00,
        ]);

        $this->get(route('park.events.index'))
            ->assertOk()
            ->assertSee('Sunset Coaster');
    }

    private function event(): ParkEvent
    {
        return ParkEvent::create([
            'park_activity_id' => $this->activity->id,
            'event_date' => now()->addDays(4)->toDateString(),
            'start_time' => '17:30',
            'capacity' => 20,
            'price' => 25.00,
        ]);
    }

    private function sell(ParkEvent $event, int $quantity): Ticket
    {
        return app(TicketSalesService::class)->sell(
            event: $event,
            quantity: $quantity,
            channel: 'online',
            buyer: $this->visitor,
            seller: null,
            method: 'card',
        );
    }
}
