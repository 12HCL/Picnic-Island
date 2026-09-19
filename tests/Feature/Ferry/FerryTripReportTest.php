<?php

namespace Tests\Feature\Ferry;

use App\Models\FerryRoute;
use App\Models\FerrySchedule;
use App\Models\Hotel;
use App\Models\HotelBooking;
use App\Models\User;
use App\Models\Vessel;
use App\Services\Ferry\FerryTicketIssueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Module 3 — trip reports: sailings in a date range with sold, boarded, revenue and
 * occupancy. Cancelled passes return the seat, so they must not count as sold or revenue.
 */
class FerryTripReportTest extends TestCase
{
    use RefreshDatabase;

    private function sailing(string $date, int $capacity = 10): FerrySchedule
    {
        $route = FerryRoute::firstOrCreate(
            ['origin' => 'Mainland Jetty', 'destination' => 'North Jetty'],
            ['duration_minutes' => 25, 'base_fare' => 45.00],
        );

        $vessel = Vessel::create(['name' => 'MV Test '.uniqid(), 'capacity' => $capacity, 'status' => 'active']);

        return FerrySchedule::create([
            'ferry_route_id' => $route->id,
            'vessel_id' => $vessel->id,
            'departure_date' => $date,
            'departure_time' => '09:30:00',
            'status' => 'scheduled',
        ]);
    }

    private function sellTicket(FerrySchedule $schedule)
    {
        $visitor = User::factory()->role('visitor')->create();
        $hotel = Hotel::firstOrCreate(['name' => 'Palm Reef Hotel'], ['star_rating' => 4]);

        $booking = HotelBooking::create([
            'user_id' => $visitor->id,
            'hotel_id' => $hotel->id,
            'reference' => 'PIB-HB-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(10)->toDateString(),
            'guests' => 1,
            'total_amount' => 300.00,
            'status' => 'confirmed',
        ]);

        return app(FerryTicketIssueService::class)->issue($schedule, $booking->id, $visitor, null, 'card');
    }

    private function operator(): User
    {
        return User::factory()->role('ferry_operator')->create();
    }

    public function test_only_a_ferry_operator_may_open_trip_reports(): void
    {
        $this->get(route('ferry.staff.reports.index'))->assertRedirect(route('login'));

        foreach (['visitor', 'hotel_staff', 'park_staff', 'admin'] as $role) {
            $this->actingAs(User::factory()->role($role)->create())
                ->get(route('ferry.staff.reports.index'))
                ->assertForbidden();
        }

        $this->actingAs($this->operator())
            ->get(route('ferry.staff.reports.index'))
            ->assertOk();
    }

    public function test_figures_exclude_cancelled_passes(): void
    {
        $schedule = $this->sailing(now()->addDays(2)->toDateString(), capacity: 10);

        $this->sellTicket($schedule);
        $this->sellTicket($schedule);
        $cancelled = $this->sellTicket($schedule);
        app(FerryTicketIssueService::class)->cancel($cancelled);

        $response = $this->actingAs($this->operator())->get(route('ferry.staff.reports.index'));

        $sailing = $response->viewData('sailings')->first();
        $this->assertSame(2, $sailing->sold_count);
        $this->assertSame(1, $sailing->cancelled_count);
        $this->assertEquals(90.00, (float) $sailing->revenue);

        $summary = $response->viewData('summary');
        $this->assertSame(2, $summary['sold']);
        $this->assertEquals(90.00, $summary['revenue']);
        $this->assertSame(20.0, (float) $summary['occupancy']);
    }

    public function test_the_date_range_filters_sailings(): void
    {
        $this->sailing('2026-10-01');
        $this->sailing('2026-11-15');

        $response = $this->actingAs($this->operator())
            ->get(route('ferry.staff.reports.index', ['from' => '2026-10-01', 'to' => '2026-10-31']));

        $this->assertCount(1, $response->viewData('sailings'));
    }

    public function test_an_unparseable_date_is_a_validation_error_not_a_crash(): void
    {
        $this->actingAs($this->operator())
            ->get(route('ferry.staff.reports.index', ['from' => 'abc']))
            ->assertSessionHasErrors('from');
    }
}
