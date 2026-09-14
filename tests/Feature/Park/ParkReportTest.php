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
 * Sales and visitor reports. The aggregates are SQL, so these tests are also what proves
 * the expressions mean the same thing on the test driver as on MySQL.
 */
class ParkReportTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $visitor;
    private ParkActivity $coaster;
    private ParkEvent $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->staff = User::factory()->role('park_staff')->create();
        $this->visitor = User::factory()->role('visitor')->create();

        $this->coaster = ParkActivity::create([
            'name' => 'Sunset Coaster',
            'type' => 'ride',
            'default_capacity' => 40,
            'base_price' => 25.00,
        ]);

        $this->event = ParkEvent::create([
            'park_activity_id' => $this->coaster->id,
            'event_date' => now()->toDateString(),
            'start_time' => '17:30',
            'capacity' => 40,
            'price' => 25.00,
        ]);
    }

    public function test_a_visitor_cannot_see_the_reports(): void
    {
        $this->actingAs($this->visitor)
            ->get(route('park.staff.reports.index'))
            ->assertForbidden();
    }

    public function test_the_report_totals_revenue_and_admissions(): void
    {
        $this->sell(2);  // 2 x 25 = 50
        $this->sell(3);  // 3 x 25 = 75

        $this->actingAs($this->staff)
            ->get(route('park.staff.reports.index'))
            ->assertOk()
            ->assertViewHas('admissions', 5)
            ->assertViewHas('tickets', 2)
            ->assertViewHas('revenue', 125.0);
    }

    public function test_revenue_is_split_by_channel_not_by_user(): void
    {
        $this->sell(2);                  // online, 50
        $this->sell(4, channel: 'gate'); // gate, 100

        $response = $this->actingAs($this->staff)->get(route('park.staff.reports.index'));

        $byChannel = $response->viewData('byChannel')->keyBy('channel');

        $this->assertSame(2, (int) $byChannel['online']->admissions);
        $this->assertSame(4, (int) $byChannel['gate']->admissions);
        $this->assertSame(100.0, (float) $byChannel['gate']->revenue);
    }

    /**
     * A cancelled ticket is money that was taken and now needs refunding. Netting it off
     * the revenue line silently would hide that, so it is reported separately.
     */
    public function test_cancelled_tickets_are_reported_apart_from_revenue(): void
    {
        $kept = $this->sell(2);   // 50
        $voided = $this->sell(4); // 100, then cancelled

        app(TicketSalesService::class)->cancel($voided);

        $this->actingAs($this->staff)
            ->get(route('park.staff.reports.index'))
            ->assertViewHas('revenue', 50.0)
            ->assertViewHas('admissions', 2)
            ->assertViewHas('cancelledTickets', 1)
            ->assertViewHas('cancelledValue', 100.0);

        $this->assertSame('valid', $kept->fresh()->status);
    }

    /**
     * Attendance is a visitor report, not a sales one: of what was sold, how much actually
     * turned up. The gap is no-shows.
     */
    public function test_attendance_compares_validated_admissions_against_sold(): void
    {
        $used = $this->sell(3);
        $this->sell(1); // never validated

        $this->actingAs($this->staff)
            ->post(route('park.staff.validate.check'), ['reference' => $used->reference]);

        $this->actingAs($this->staff)
            ->get(route('park.staff.reports.index'))
            ->assertViewHas('admissions', 4)
            ->assertViewHas('admitted', 3)
            ->assertViewHas('attendanceRate', 75.0);
    }

    public function test_the_breakdown_by_activity_ranks_what_sold(): void
    {
        $show = ParkActivity::create([
            'name' => 'Dolphin Show',
            'type' => 'show',
            'default_capacity' => 100,
            'base_price' => 10.00,
        ]);

        $showEvent = ParkEvent::create([
            'park_activity_id' => $show->id,
            'event_date' => now()->toDateString(),
            'start_time' => '11:00',
            'capacity' => 100,
            'price' => 10.00,
        ]);

        $this->sell(2);                          // coaster, 50
        $this->sell(1, event: $showEvent);       // show, 10

        $rows = $this->actingAs($this->staff)
            ->get(route('park.staff.reports.index'))
            ->viewData('byActivity');

        // Ordered by revenue, so the coaster leads.
        $this->assertSame('Sunset Coaster', $rows->first()->activity);
        $this->assertSame(2, $rows->count());
    }

    public function test_the_window_excludes_events_outside_it(): void
    {
        $this->sell(2); // today

        $lastMonth = ParkEvent::create([
            'park_activity_id' => $this->coaster->id,
            'event_date' => now()->subMonths(2)->toDateString(),
            'start_time' => '17:30',
            'capacity' => 40,
            'price' => 25.00,
            'seats_taken' => 10,
            'status' => 'completed',
        ]);

        // Written directly rather than sold: TicketSalesService refuses a past event, which
        // is correct — this is historical data the report has to be able to read, not a
        // sale being made now.
        Ticket::create([
            'user_id' => $this->visitor->id,
            'park_event_id' => $lastMonth->id,
            'reference' => 'PIB-TK-900001',
            'quantity' => 10,
            'unit_price' => 25.00,
            'channel' => 'online',
            'status' => 'used',
        ]);

        // Default window is the current month, so the older event is out.
        $this->actingAs($this->staff)
            ->get(route('park.staff.reports.index'))
            ->assertViewHas('admissions', 2);

        // Widen the window and it appears.
        $this->actingAs($this->staff)
            ->get(route('park.staff.reports.index', [
                'from' => now()->subMonths(3)->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertViewHas('admissions', 12);
    }

    public function test_the_channel_filter_narrows_the_whole_report(): void
    {
        $this->sell(2);                  // online
        $this->sell(5, channel: 'gate'); // gate

        $this->actingAs($this->staff)
            ->get(route('park.staff.reports.index', ['channel' => 'gate']))
            ->assertViewHas('admissions', 5)
            ->assertViewHas('revenue', 125.0);
    }

    public function test_an_empty_window_reports_zeroes_rather_than_failing(): void
    {
        $this->actingAs($this->staff)
            ->get(route('park.staff.reports.index'))
            ->assertOk()
            ->assertViewHas('revenue', 0.0)
            ->assertViewHas('admissions', 0)
            ->assertViewHas('attendanceRate', 0.0);
    }

    private function sell(int $quantity, string $channel = 'online', ?ParkEvent $event = null): Ticket
    {
        return app(TicketSalesService::class)->sell(
            event: $event ?? $this->event,
            quantity: $quantity,
            channel: $channel,
            buyer: $channel === 'online' ? $this->visitor : null,
            seller: $channel === 'gate' ? $this->staff : null,
            method: 'card',
        );
    }
}
