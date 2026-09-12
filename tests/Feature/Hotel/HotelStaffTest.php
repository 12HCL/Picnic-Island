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

class HotelStaffTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $visitor;
    private Hotel $hotel;
    private RoomType $roomType;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->staff = User::factory()->role('hotel_staff')->create();
        $this->visitor = User::factory()->role('visitor')->create();

        $this->hotel = Hotel::create([
            'name' => 'Sunset Lagoon Hotel',
            'description' => 'Beautiful lagoon resort',
            'address' => 'Picnic Island Lagoon Road',
            'star_rating' => 4,
        ]);

        $this->roomType = RoomType::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Lagoon Suite',
            'description' => 'Suite over the water',
            'base_price' => 2000.00,
            'max_occupancy' => 3,
        ]);

        $this->room = Room::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'room_number' => '201',
            'floor' => 2,
            'status' => 'available',
        ]);
    }

    public function test_visitor_cannot_access_staff_dashboard(): void
    {
        $response = $this->actingAs($this->visitor)->get(route('hotel.dashboard'));
        $response->assertForbidden();
    }

    public function test_hotel_staff_can_view_dashboard(): void
    {
        $response = $this->actingAs($this->staff)->get(route('hotel.dashboard'));
        $response->assertOk();
        $response->assertSee('Hotel Staff Dashboard');
    }

    public function test_hotel_staff_can_confirm_booking(): void
    {
        $booking = HotelBooking::create([
            'user_id' => $this->visitor->id,
            'hotel_id' => $this->hotel->id,
            'reference' => 'PIB-HB-000001',
            'check_in' => now()->addDays(1)->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'guests' => 2,
            'total_amount' => 4000.00,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->staff)->post(route('hotel.staff.bookings.confirm', $booking));
        $response->assertRedirect(route('hotel.bookings.show', $booking));

        $this->assertEquals('confirmed', $booking->fresh()->status);
    }

    public function test_hotel_staff_can_check_in_and_check_out_guest(): void
    {
        $booking = HotelBooking::create([
            'user_id' => $this->visitor->id,
            'hotel_id' => $this->hotel->id,
            'reference' => 'PIB-HB-000002',
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'guests' => 2,
            'total_amount' => 4000.00,
            'status' => 'confirmed',
        ]);

        // Check in
        $this->actingAs($this->staff)->post(route('hotel.staff.bookings.check-in', $booking));
        $this->assertEquals('checked_in', $booking->fresh()->status);

        // Check out
        $this->actingAs($this->staff)->post(route('hotel.staff.bookings.check-out', $booking));
        $this->assertEquals('completed', $booking->fresh()->status);
    }

    public function test_hotel_staff_can_manage_rooms(): void
    {
        // View rooms
        $indexResponse = $this->actingAs($this->staff)->get(route('hotel.staff.rooms.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('201');

        // Create room
        $createResponse = $this->actingAs($this->staff)->post(route('hotel.staff.rooms.store'), [
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'room_number' => '202',
            'floor' => 2,
            'status' => 'available',
        ]);
        $createResponse->assertRedirect(route('hotel.staff.rooms.index'));
        $this->assertDatabaseHas('rooms', ['room_number' => '202']);

        // Delete room
        $newRoom = Room::where('room_number', '202')->first();
        $deleteResponse = $this->actingAs($this->staff)->delete(route('hotel.staff.rooms.destroy', $newRoom));
        $deleteResponse->assertRedirect(route('hotel.staff.rooms.index'));
        $this->assertDatabaseMissing('rooms', ['room_number' => '202']);
    }

    public function test_hotel_staff_can_view_reports(): void
    {
        $response = $this->actingAs($this->staff)->get(route('hotel.staff.reports.index'));
        $response->assertOk();
        $response->assertSee('Hotel Reports');
    }
}
