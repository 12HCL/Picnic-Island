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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class AdminDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->travelTo('2026-09-14 12:00:00');
    }

    public function test_an_admin_sees_the_dashboard_and_its_contract_data(): void
    {
        $admin = User::factory()->role('admin')->create();
        User::factory()->role('visitor')->create(['is_active' => false]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewIs('admin.dashboard');
        $response->assertViewHas('stats', function (array $stats): bool {
            return array_keys($stats) === [
                'users_total',
                'hotel_bookings_today',
                'ferry_tickets_today',
                'park_tickets_today',
                'revenue_this_month',
            ] && $stats['users_total'] === 2;
        });
        $response->assertViewHas(
            'recent_bookings',
            fn ($bookings): bool => $bookings instanceof Collection,
        );
        $response->assertSee('MVR 0.00');
        $response->assertSee('No hotel bookings have been made yet.');
    }

    public function test_a_visitor_cannot_open_the_admin_dashboard(): void
    {
        $visitor = User::factory()->role('visitor')->create();

        $this->actingAs($visitor)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_today_counts_include_today_and_exclude_yesterday_for_all_three_sales_types(): void
    {
        $admin = User::factory()->role('admin')->create();
        $visitor = User::factory()->role('visitor')->create();
        $hotel = $this->hotel();
        $todayBooking = $this->hotelBooking($visitor, $hotel, 'HB-TODAY');
        $yesterdayBooking = $this->hotelBooking($visitor, $hotel, 'HB-YESTERDAY');
        $this->moveToYesterday($yesterdayBooking);

        $schedule = $this->ferrySchedule();
        $this->ferryTicket($visitor, $todayBooking, $schedule, 'FT-TODAY');
        $yesterdayFerryTicket = $this->ferryTicket(
            $visitor,
            $todayBooking,
            $schedule,
            'FT-YESTERDAY',
        );
        $this->moveToYesterday($yesterdayFerryTicket);

        $event = $this->parkEvent();
        $this->parkTicket($visitor, $event, 'PT-TODAY');
        $yesterdayParkTicket = $this->parkTicket($visitor, $event, 'PT-YESTERDAY');
        $this->moveToYesterday($yesterdayParkTicket);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('stats', function (array $stats): bool {
                return $stats['hotel_bookings_today'] === 1
                    && $stats['ferry_tickets_today'] === 1
                    && $stats['park_tickets_today'] === 1;
            });
    }

    public function test_monthly_revenue_includes_anonymous_paid_gate_sales_only_in_this_month(): void
    {
        $admin = User::factory()->role('admin')->create();
        $event = $this->parkEvent();
        $includedTicket = $this->parkTicket(null, $event, 'PT-PAID-CURRENT');
        $pendingTicket = $this->parkTicket(null, $event, 'PT-PENDING');
        $lastMonthTicket = $this->parkTicket(null, $event, 'PT-PAID-LAST');

        $this->payment($includedTicket, 'PM-PAID-CURRENT', 125.50, 'paid', now());
        $this->payment($pendingTicket, 'PM-PENDING', 50.00, 'pending', now());
        $this->payment(
            $lastMonthTicket,
            'PM-PAID-LAST',
            200.00,
            'paid',
            now()->subMonth()->endOfMonth(),
        );

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas(
                'stats',
                fn (array $stats): bool => (float) $stats['revenue_this_month'] === 125.50,
            )
            ->assertSee('MVR 125.50');
    }

    public function test_recent_bookings_are_the_latest_five_with_user_and_hotel_eager_loaded(): void
    {
        $admin = User::factory()->role('admin')->create();
        $visitor = User::factory()->role('visitor')->create();
        $hotel = $this->hotel();

        for ($number = 1; $number <= 6; $number++) {
            $booking = $this->hotelBooking(
                $visitor,
                $hotel,
                sprintf('HB-RECENT-%02d', $number),
            );
            $createdAt = now()->subDays(6 - $number);
            $booking->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->saveQuietly();
        }

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('recent_bookings', function (Collection $bookings): bool {
            return $bookings->pluck('reference')->all() === [
                'HB-RECENT-06',
                'HB-RECENT-05',
                'HB-RECENT-04',
                'HB-RECENT-03',
                'HB-RECENT-02',
            ] && $bookings->every(fn (HotelBooking $booking): bool => $booking->relationLoaded('user') && $booking->relationLoaded('hotel'));
        });
        $response->assertSee('HB-RECENT-06');
        $response->assertDontSee('HB-RECENT-01');
    }

    private function hotel(): Hotel
    {
        return Hotel::create([
            'name' => 'Coral Bay Resort',
            'description' => 'Beachside hotel',
            'address' => 'North Shore, Picnic Island',
            'star_rating' => 5,
        ]);
    }

    private function hotelBooking(User $user, Hotel $hotel, string $reference): HotelBooking
    {
        return HotelBooking::create([
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
            'reference' => $reference,
            'check_in' => '2026-09-16',
            'check_out' => '2026-09-18',
            'guests' => 2,
            'total_amount' => 450.00,
            'status' => 'confirmed',
        ]);
    }

    private function ferrySchedule(): FerrySchedule
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

        return FerrySchedule::create([
            'ferry_route_id' => $route->id,
            'vessel_id' => $vessel->id,
            'departure_date' => '2026-09-16',
            'departure_time' => '09:00:00',
            'seats_taken' => 2,
            'status' => 'scheduled',
        ]);
    }

    private function ferryTicket(
        User $user,
        HotelBooking $booking,
        FerrySchedule $schedule,
        string $reference,
    ): FerryTicket {
        return FerryTicket::create([
            'user_id' => $user->id,
            'ferry_schedule_id' => $schedule->id,
            'hotel_booking_id' => $booking->id,
            'reference' => $reference,
            'fare' => 75.00,
            'status' => 'issued',
            'issued_at' => now(),
        ]);
    }

    private function parkEvent(): ParkEvent
    {
        $activity = ParkActivity::create([
            'name' => 'Lagoon Show',
            'type' => 'show',
            'description' => 'A family show by the lagoon.',
            'default_capacity' => 100,
            'base_price' => 50.00,
            'is_active' => true,
        ]);

        return ParkEvent::create([
            'park_activity_id' => $activity->id,
            'event_date' => '2026-09-16',
            'start_time' => '14:00:00',
            'capacity' => 100,
            'seats_taken' => 2,
            'price' => 50.00,
            'status' => 'scheduled',
        ]);
    }

    private function parkTicket(?User $user, ParkEvent $event, string $reference): Ticket
    {
        return Ticket::create([
            'user_id' => $user?->id,
            'park_event_id' => $event->id,
            'reference' => $reference,
            'quantity' => 1,
            'unit_price' => 50.00,
            'channel' => $user === null ? 'gate' : 'online',
            'status' => 'valid',
        ]);
    }

    private function payment(
        Ticket $ticket,
        string $reference,
        float $amount,
        string $status,
        mixed $paidAt,
    ): Payment {
        return Payment::create([
            'user_id' => null,
            'ticket_id' => $ticket->id,
            'reference' => $reference,
            'amount' => $amount,
            'method' => 'cash',
            'status' => $status,
            'paid_at' => $paidAt,
        ]);
    }

    private function moveToYesterday(Model $model): void
    {
        $yesterday = now()->subDay();

        $model->forceFill([
            'created_at' => $yesterday,
            'updated_at' => $yesterday,
        ])->saveQuietly();
    }
}
