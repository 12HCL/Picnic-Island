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
 * UC-16 — on-site validation, plus the capacity dashboard and the staff landing page.
 */
class TicketValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $visitor;
    private ParkActivity $activity;
    private ParkEvent $today;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->staff = User::factory()->role('park_staff')->create();
        $this->visitor = User::factory()->role('visitor')->create(['name' => 'Aishath Visitor']);

        $this->activity = ParkActivity::create([
            'name' => 'Sunset Coaster',
            'type' => 'ride',
            'default_capacity' => 40,
            'base_price' => 25.00,
        ]);

        $this->today = ParkEvent::create([
            'park_activity_id' => $this->activity->id,
            'event_date' => now()->toDateString(),
            'start_time' => '17:30',
            'capacity' => 20,
            'price' => 25.00,
        ]);
    }

    // ── Access ────────────────────────────────────────────────────────────────

    public function test_a_visitor_cannot_reach_the_validation_terminal(): void
    {
        $this->actingAs($this->visitor)
            ->get(route('park.staff.validate'))
            ->assertForbidden();
    }

    public function test_park_staff_can_open_the_terminal(): void
    {
        $this->actingAs($this->staff)
            ->get(route('park.staff.validate'))
            ->assertOk();
    }

    // ── Main flow ─────────────────────────────────────────────────────────────

    public function test_a_valid_ticket_for_today_is_admitted_and_marked_used(): void
    {
        $ticket = $this->sell($this->today);

        $this->actingAs($this->staff)
            ->post(route('park.staff.validate.check'), ['reference' => $ticket->reference])
            ->assertSessionHas('validation.verdict', 'admitted');

        $ticket->refresh();
        $this->assertSame('used', $ticket->status);
        $this->assertNotNull($ticket->validated_at);
    }

    /**
     * The verdict has to survive the redirect and actually render. Every other test here
     * asserts the flashed session value and stops, which is what let a 500 through: the
     * controller flashed the Ticket model, the session serialised it to an array, and the
     * view died on `$t->reference`. Following the redirect is what catches that.
     */
    public function test_the_verdict_renders_on_the_terminal_after_the_redirect(): void
    {
        $ticket = $this->sell($this->today, 2);

        // from() sets the referer: back() has nothing to go back to in a test otherwise.
        $this->actingAs($this->staff)
            ->from(route('park.staff.validate'))
            ->post(route('park.staff.validate.check'), ['reference' => $ticket->reference])
            ->assertRedirect(route('park.staff.validate'));

        $this->actingAs($this->staff)
            ->get(route('park.staff.validate'))
            ->assertOk()
            ->assertSee('ADMIT')
            ->assertSee($ticket->reference)
            ->assertSee('Sunset Coaster');
    }

    public function test_a_rejection_also_renders_with_its_ticket_details(): void
    {
        $ticket = $this->sell($this->today, 1);
        $ticket->update(['status' => 'used', 'validated_at' => now()->subHour()]);

        $this->actingAs($this->staff)
            ->post(route('park.staff.validate.check'), ['reference' => $ticket->reference]);

        $this->actingAs($this->staff)
            ->get(route('park.staff.validate'))
            ->assertOk()
            ->assertSee('DO NOT ADMIT')
            ->assertSee($ticket->reference);
    }

    // ── E1: already used ──────────────────────────────────────────────────────

    public function test_a_used_ticket_is_refused_the_second_time(): void
    {
        $ticket = $this->sell($this->today);

        $this->actingAs($this->staff)
            ->post(route('park.staff.validate.check'), ['reference' => $ticket->reference]);

        $second = $this->actingAs($this->staff)
            ->post(route('park.staff.validate.check'), ['reference' => $ticket->reference]);

        $second->assertSessionHas('validation.verdict', 'rejected');
        $this->assertStringContainsString('Already used', session('validation')['reason']);
    }

    // ── E2: wrong date / wrong state ──────────────────────────────────────────

    public function test_a_ticket_for_another_day_is_refused(): void
    {
        $tomorrow = ParkEvent::create([
            'park_activity_id' => $this->activity->id,
            'event_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'capacity' => 20,
            'price' => 25.00,
        ]);

        $ticket = $this->sell($tomorrow);

        $this->actingAs($this->staff)
            ->post(route('park.staff.validate.check'), ['reference' => $ticket->reference])
            ->assertSessionHas('validation.verdict', 'rejected');

        // Refused, and left untouched for the day it is actually for.
        $this->assertSame('valid', $ticket->fresh()->status);
        $this->assertNull($ticket->fresh()->validated_at);
    }

    public function test_a_cancelled_ticket_is_refused(): void
    {
        $ticket = $this->sell($this->today);
        app(TicketSalesService::class)->cancel($ticket);

        $this->actingAs($this->staff)
            ->post(route('park.staff.validate.check'), ['reference' => $ticket->reference])
            ->assertSessionHas('validation.verdict', 'rejected');

        $this->assertSame('cancelled', $ticket->fresh()->status);
    }

    public function test_a_ticket_for_a_cancelled_event_is_refused(): void
    {
        $ticket = $this->sell($this->today);
        $this->today->update(['status' => 'cancelled']);

        $this->actingAs($this->staff)
            ->post(route('park.staff.validate.check'), ['reference' => $ticket->reference])
            ->assertSessionHas('validation.verdict', 'rejected');

        $this->assertSame('valid', $ticket->fresh()->status);
    }

    public function test_an_unknown_reference_is_refused(): void
    {
        $this->actingAs($this->staff)
            ->post(route('park.staff.validate.check'), ['reference' => 'PIB-TK-999999'])
            ->assertSessionHas('validation.verdict', 'rejected');
    }

    // ── A1: lookup by name ────────────────────────────────────────────────────

    public function test_staff_can_find_todays_ticket_by_visitor_name(): void
    {
        $ticket = $this->sell($this->today);

        $this->actingAs($this->staff)
            ->get(route('park.staff.validate', ['name' => 'Aishath']))
            ->assertOk()
            ->assertSee($ticket->reference);
    }

    public function test_the_name_search_does_not_return_another_days_ticket(): void
    {
        $tomorrow = ParkEvent::create([
            'park_activity_id' => $this->activity->id,
            'event_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'capacity' => 20,
            'price' => 25.00,
        ]);
        $ticket = $this->sell($tomorrow);

        $this->actingAs($this->staff)
            ->get(route('park.staff.validate', ['name' => 'Aishath']))
            ->assertOk()
            ->assertDontSee($ticket->reference);
    }

    // ── Capacity dashboard ────────────────────────────────────────────────────

    public function test_the_capacity_dashboard_reports_how_full_an_event_is(): void
    {
        $this->sell($this->today, 5); // 5 of 20 = 25%

        $this->actingAs($this->staff)
            ->get(route('park.staff.capacity'))
            ->assertOk()
            ->assertSee('Sunset Coaster')
            ->assertSee('25');
    }

    public function test_a_visitor_cannot_see_the_capacity_dashboard(): void
    {
        $this->actingAs($this->visitor)
            ->get(route('park.staff.capacity'))
            ->assertForbidden();
    }

    // ── Staff landing page ────────────────────────────────────────────────────

    public function test_park_staff_land_on_their_own_dashboard(): void
    {
        $this->actingAs($this->staff)
            ->get(route('park.dashboard'))
            ->assertOk()
            ->assertSee('Sunset Coaster');
    }

    /**
     * DashboardController maps park_staff to 'park.dashboard' and only redirects when
     * Route::has() finds it. Before this route existed the redirect silently fell through.
     */
    public function test_the_generic_dashboard_redirects_park_staff_to_the_park_dashboard(): void
    {
        $this->actingAs($this->staff)
            ->get('/dashboard')
            ->assertRedirect(route('park.dashboard'));
    }

    private function sell(ParkEvent $event, int $quantity = 1): Ticket
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
