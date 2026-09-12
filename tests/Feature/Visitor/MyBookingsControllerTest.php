<?php

namespace Tests\Feature\Visitor;

use App\Models\FerryRoute;
use App\Models\FerrySchedule;
use App\Models\FerryTicket;
use App\Models\Hotel;
use App\Models\HotelBooking;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyBookingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visitor_sees_only_their_own_hotel_and_ferry_bookings(): void
    {
        $visitor = User::factory()->role('visitor')->create();
        $otherVisitor = User::factory()->role('visitor')->create();
        $hotel = Hotel::create([
            'name' => 'Coral Bay Resort',
            'description' => 'Beachside hotel',
            'address' => 'North Shore, Picnic Island',
            'star_rating' => 5,
        ]);

        $ownBooking = $this->hotelBooking($visitor, $hotel, 'PIB-HB-OWN');
        $otherBooking = $this->hotelBooking($otherVisitor, $hotel, 'PIB-HB-OTHER');
        $schedule = $this->ferrySchedule();
        $ownTicket = $this->ferryTicket($visitor, $ownBooking, $schedule, 'PIB-FT-OWN');
        $otherTicket = $this->ferryTicket($otherVisitor, $otherBooking, $schedule, 'PIB-FT-OTHER');

        $response = $this->actingAs($visitor)->get(route('visitor.bookings.index'));

        $response->assertOk();
        $response->assertViewIs('visitor.bookings.index');
        $response->assertSee($ownBooking->reference);
        $response->assertSee($ownTicket->reference);
        $response->assertDontSee($otherBooking->reference);
        $response->assertDontSee($otherTicket->reference);
        $response->assertViewHas('hotelBookings', function ($bookings) use ($ownBooking) {
            return $bookings->count() === 1
                && $bookings->first()->is($ownBooking)
                && $bookings->first()->relationLoaded('hotel');
        });
        $response->assertViewHas('ferryTickets', function ($tickets) use ($ownTicket) {
            $ticket = $tickets->first();

            return $tickets->count() === 1
                && $ticket->is($ownTicket)
                && $ticket->relationLoaded('schedule')
                && $ticket->schedule->relationLoaded('route')
                && $ticket->schedule->relationLoaded('vessel');
        });
    }

    public function test_a_visitor_with_no_bookings_sees_the_empty_state(): void
    {
        $visitor = User::factory()->role('visitor')->create();

        $this->actingAs($visitor)
            ->get(route('visitor.bookings.index'))
            ->assertOk()
            ->assertSee('You do not have any hotel bookings or ferry tickets yet.');
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('visitor.bookings.index'))->assertRedirect(route('login'));
    }

    public function test_a_non_visitor_cannot_open_the_visitor_dashboard(): void
    {
        $hotelStaff = User::factory()->role('hotel_staff')->create();

        $this->actingAs($hotelStaff)
            ->get(route('visitor.bookings.index'))
            ->assertForbidden();
    }

    private function hotelBooking(User $user, Hotel $hotel, string $reference): HotelBooking
    {
        return HotelBooking::create([
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
            'reference' => $reference,
            'check_in' => '2026-09-12',
            'check_out' => '2026-09-15',
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
            'departure_date' => '2026-09-12',
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
}
