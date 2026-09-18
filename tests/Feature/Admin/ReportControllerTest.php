<?php

namespace Tests\Feature\Admin;

use App\Models\FerryRoute;
use App\Models\FerrySchedule;
use App\Models\FerryTicket;
use App\Models\Hotel;
use App\Models\HotelBooking;
use App\Models\ParkActivity;
use App\Models\ParkEvent;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Vessel;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->travelTo('2026-09-18 12:00:00');
    }

    public function test_only_an_admin_can_open_system_reports(): void
    {
        $visitor = User::factory()->role('visitor')->create();

        $this->get(route('admin.reports.index'))
            ->assertRedirect(route('login'));

        $this->actingAs($visitor)
            ->get(route('admin.reports.index'))
            ->assertForbidden();
    }

    public function test_an_admin_sees_the_empty_month_to_date_report_and_navigation_link(): void
    {
        $admin = User::factory()->role('admin')->create();

        $this->actingAs($admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertViewIs('admin.reports.index')
            ->assertViewHas('from', '2026-09-01')
            ->assertViewHas('to', '2026-09-30')
            ->assertViewHas('summary', [
                'hotel_bookings' => 0,
                'ferry_tickets' => 0,
                'park_admissions' => 0,
                'paid_transactions' => 0,
                'revenue' => 0.0,
            ])
            ->assertSee('No paid transactions in this date range.')
            ->assertSee('href="'.route('admin.reports.index').'"', false);
    }

    public function test_the_end_date_cannot_precede_the_start_date(): void
    {
        $admin = User::factory()->role('admin')->create();

        $this->actingAs($admin)
            ->from(route('admin.reports.index'))
            ->get(route('admin.reports.index', [
                'from' => '2026-09-18',
                'to' => '2026-09-17',
            ]))
            ->assertRedirect(route('admin.reports.index'))
            ->assertSessionHasErrors('to');
    }

    public function test_the_report_consolidates_activity_and_paid_revenue_for_the_selected_window(): void
    {
        $admin = User::factory()->role('admin')->create();
        $visitor = User::factory()->role('visitor')->create();
        $hotel = Hotel::create([
            'name' => 'Coral Bay Resort',
            'description' => 'Beachside hotel',
            'address' => 'North Shore, Picnic Island',
            'star_rating' => 5,
        ]);

        $includedBooking = $this->hotelBooking($visitor, $hotel, 'HB-IN', '2026-09-16');
        $this->hotelBooking($visitor, $hotel, 'HB-OUT', '2026-09-20');
        $ferryTicket = $this->ferryTicket($visitor, $includedBooking);
        $parkTicket = $this->parkTicket('PT-GATE', 2);
        $pendingParkTicket = $this->parkTicket('PT-PENDING', 1, '15:00:00');

        $this->payment([
            'user_id' => $visitor->id,
            'hotel_booking_id' => $includedBooking->id,
            'reference' => 'PM-HOTEL',
            'amount' => 450.00,
            'method' => 'card',
            'status' => 'paid',
            'paid_at' => '2026-09-15 10:00:00',
        ]);
        $this->payment([
            'user_id' => $visitor->id,
            'ferry_ticket_id' => $ferryTicket->id,
            'reference' => 'PM-FERRY',
            'amount' => 75.00,
            'method' => 'transfer',
            'status' => 'paid',
            'paid_at' => '2026-09-16 10:00:00',
        ]);
        $this->payment([
            'user_id' => null,
            'ticket_id' => $parkTicket->id,
            'reference' => 'PM-GATE',
            'amount' => 100.00,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => '2026-09-17 23:59:59',
        ]);
        $this->payment([
            'user_id' => null,
            'ticket_id' => $pendingParkTicket->id,
            'reference' => 'PM-PENDING',
            'amount' => 50.00,
            'method' => 'cash',
            'status' => 'pending',
            'paid_at' => '2026-09-17 12:00:00',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.index', [
            'from' => '2026-09-15',
            'to' => '2026-09-17',
        ]));

        $response->assertOk()
            ->assertViewHas('summary', [
                'hotel_bookings' => 1,
                'ferry_tickets' => 1,
                'park_admissions' => 3,
                'paid_transactions' => 3,
                'revenue' => 625.0,
            ])
            ->assertViewHas('breakdown', function (Collection $breakdown): bool {
                return $breakdown->pluck('transactions', 'module')->all() === [
                    'Hotel' => 1,
                    'Ferry' => 1,
                    'Theme park & beach' => 1,
                ];
            })
            ->assertViewHas('recentPayments', fn (Collection $payments): bool => $payments->pluck('reference')->all() === [
                'PM-GATE',
                'PM-FERRY',
                'PM-HOTEL',
            ])
            ->assertSee('MVR 625.00')
            ->assertSee('PM-GATE')
            ->assertDontSee('PM-PENDING');
    }

    private function hotelBooking(
        User $visitor,
        Hotel $hotel,
        string $reference,
        string $checkIn,
    ): HotelBooking {
        return HotelBooking::create([
            'user_id' => $visitor->id,
            'hotel_id' => $hotel->id,
            'reference' => $reference,
            'check_in' => $checkIn,
            'check_out' => date('Y-m-d', strtotime($checkIn.' +2 days')),
            'guests' => 2,
            'total_amount' => 450.00,
            'status' => 'confirmed',
        ]);
    }

    private function ferryTicket(User $visitor, HotelBooking $booking): FerryTicket
    {
        $route = FerryRoute::create([
            'origin' => 'Male',
            'destination' => 'Picnic Island',
            'duration_minutes' => 45,
            'base_fare' => 75.00,
        ]);
        $vessel = Vessel::create([
            'name' => 'Island Voyager',
            'capacity' => 80,
            'status' => 'active',
        ]);
        $schedule = FerrySchedule::create([
            'ferry_route_id' => $route->id,
            'vessel_id' => $vessel->id,
            'departure_date' => '2026-09-16',
            'departure_time' => '09:00:00',
            'seats_taken' => 1,
            'status' => 'scheduled',
        ]);

        return FerryTicket::create([
            'user_id' => $visitor->id,
            'ferry_schedule_id' => $schedule->id,
            'hotel_booking_id' => $booking->id,
            'reference' => 'FT-IN',
            'fare' => 75.00,
            'status' => 'issued',
            'issued_at' => '2026-09-16 08:00:00',
        ]);
    }

    private function parkTicket(string $reference, int $quantity, string $startTime = '14:00:00'): Ticket
    {
        $activity = ParkActivity::create([
            'name' => 'Lagoon Show '.$reference,
            'type' => 'show',
            'description' => 'A family show by the lagoon.',
            'default_capacity' => 100,
            'base_price' => 50.00,
            'is_active' => true,
        ]);
        $event = ParkEvent::create([
            'park_activity_id' => $activity->id,
            'event_date' => '2026-09-16',
            'start_time' => $startTime,
            'capacity' => 100,
            'seats_taken' => $quantity,
            'price' => 50.00,
            'status' => 'scheduled',
        ]);

        return Ticket::create([
            'user_id' => null,
            'park_event_id' => $event->id,
            'reference' => $reference,
            'quantity' => $quantity,
            'unit_price' => 50.00,
            'channel' => 'gate',
            'status' => 'valid',
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function payment(array $attributes): Payment
    {
        return Payment::create($attributes);
    }
}
