<?php

namespace Tests\Feature\Hotel;

use App\Models\Hotel;
use App\Models\HotelBooking;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotelBookingTest extends TestCase
{
    use RefreshDatabase;

    private User $visitor;
    private Hotel $hotel;
    private RoomType $roomType;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->visitor = User::factory()->role('visitor')->create();

        $this->hotel = Hotel::create([
            'name' => 'Coral Reef Resort',
            'description' => 'Luxury island resort',
            'address' => 'Picnic Island Beachfront',
            'star_rating' => 5,
        ]);

        $this->roomType = RoomType::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Deluxe Ocean View',
            'description' => 'Spacious room with sea view',
            'base_price' => 1500.00,
            'max_occupancy' => 2,
        ]);

        $this->room = Room::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'room_number' => '101',
            'floor' => 1,
            'status' => 'available',
        ]);
    }

    public function test_public_can_view_hotels_list(): void
    {
        $response = $this->get(route('hotel.index'));

        $response->assertOk();
        $response->assertSee('Coral Reef Resort');
    }

    public function test_public_can_view_single_hotel(): void
    {
        $response = $this->get(route('hotel.show', $this->hotel));

        $response->assertOk();
        $response->assertSee('Coral Reef Resort');
        $response->assertSee('Deluxe Ocean View');
    }

    public function test_visitor_can_view_booking_form(): void
    {
        $response = $this->actingAs($this->visitor)->get(route('hotel.bookings.create'));

        $response->assertOk();
        $response->assertSee('Book a Hotel Stay');
    }

    public function test_visitor_can_create_booking(): void
    {
        $checkIn = now()->addDays(2)->toDateString();
        $checkOut = now()->addDays(5)->toDateString();

        $response = $this->actingAs($this->visitor)->post(route('hotel.bookings.store'), [
            'hotel_id' => $this->hotel->id,
            'room_ids' => [$this->room->id],
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => 2,
        ]);

        $booking = HotelBooking::first();
        $this->assertNotNull($booking);
        $this->assertEquals($this->visitor->id, $booking->user_id);
        $this->assertEquals('pending', $booking->status);
        $this->assertEquals(4500.00, (float) $booking->total_amount); // 3 nights * 1500.00
        $this->assertStringStartsWith('PIB-HB-', $booking->reference);

        $response->assertRedirect(route('hotel.bookings.show', $booking));
    }

    public function test_visitor_cannot_view_another_visitors_booking(): void
    {
        $otherVisitor = User::factory()->role('visitor')->create();

        $booking = HotelBooking::create([
            'user_id' => $otherVisitor->id,
            'hotel_id' => $this->hotel->id,
            'reference' => 'PIB-HB-000099',
            'check_in' => now()->addDays(1)->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'guests' => 1,
            'total_amount' => 3000.00,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->visitor)->get(route('hotel.bookings.show', $booking));
        $response->assertForbidden();
    }

    public function test_visitor_can_pay_for_booking(): void
    {
        $booking = HotelBooking::create([
            'user_id' => $this->visitor->id,
            'hotel_id' => $this->hotel->id,
            'reference' => 'PIB-HB-000001',
            'check_in' => now()->addDays(1)->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'guests' => 2,
            'total_amount' => 3000.00,
            'status' => 'confirmed',
        ]);

        // Access pay screen
        $payScreenResponse = $this->actingAs($this->visitor)->get(route('hotel.bookings.pay', $booking));
        $payScreenResponse->assertOk();
        $payScreenResponse->assertSee('Confirm Booking Payment');

        // Submit payment
        $paymentResponse = $this->actingAs($this->visitor)->post(route('hotel.bookings.pay.store', $booking), [
            'method' => 'card',
        ]);

        $paymentResponse->assertRedirect(route('hotel.bookings.show', $booking));
        $this->assertDatabaseHas('payments', [
            'hotel_booking_id' => $booking->id,
            'method' => 'card',
            'status' => 'paid',
        ]);
    }

    public function test_ajax_availability_returns_available_rooms(): void
    {
        $checkIn = now()->addDays(2)->toDateString();
        $checkOut = now()->addDays(5)->toDateString();

        $response = $this->actingAs($this->visitor)->getJson(
            route('ajax.hotel.availability', [
                'hotel_id' => $this->hotel->id,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
            ])
        );

        $response->assertOk();
        $response->assertJsonStructure(['rooms']);
        $response->assertJsonFragment(['room_number' => '101']);
    }

    public function test_owning_visitor_can_view_their_booking(): void
    {
        $booking = HotelBooking::create([
            'user_id' => $this->visitor->id,
            'hotel_id' => $this->hotel->id,
            'reference' => 'PIB-HB-000010',
            'check_in' => now()->addDays(1)->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'guests' => 1,
            'total_amount' => 1500.00,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->visitor)->get(route('hotel.bookings.show', $booking));
        $response->assertOk();
    }

    public function test_hotel_staff_can_view_any_booking(): void
    {
        $staff = User::factory()->role('hotel_staff')->create();
        $booking = HotelBooking::create([
            'user_id' => $this->visitor->id,
            'hotel_id' => $this->hotel->id,
            'reference' => 'PIB-HB-000011',
            'check_in' => now()->addDays(1)->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'guests' => 1,
            'total_amount' => 1500.00,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($staff)->get(route('hotel.bookings.show', $booking));
        $response->assertOk();
    }

    public function test_unrelated_roles_cannot_view_visitor_booking(): void
    {
        $booking = HotelBooking::create([
            'user_id' => $this->visitor->id,
            'hotel_id' => $this->hotel->id,
            'reference' => 'PIB-HB-000012',
            'check_in' => now()->addDays(1)->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'guests' => 1,
            'total_amount' => 1500.00,
            'status' => 'pending',
        ]);

        $otherVisitor = User::factory()->role('visitor')->create();
        $ferryOp = User::factory()->role('ferry_operator')->create();
        $parkStaff = User::factory()->role('park_staff')->create();
        $admin = User::factory()->role('admin')->create();

        // Other visitor receives 403
        $this->actingAs($otherVisitor)->get(route('hotel.bookings.show', $booking))->assertForbidden();

        // Non-hotel roles receive 403 (verifies Bug #1 resolution)
        $this->actingAs($ferryOp)->get(route('hotel.bookings.show', $booking))->assertForbidden();
        $this->actingAs($parkStaff)->get(route('hotel.bookings.show', $booking))->assertForbidden();
        $this->actingAs($admin)->get(route('hotel.bookings.show', $booking))->assertForbidden();
    }
}
