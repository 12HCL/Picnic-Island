<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\HotelBooking;
use App\Models\User;
use App\Services\Hotel\HotelBookingGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class HotelBookingGatewayTest extends TestCase
{
    use RefreshDatabase;

    private HotelBookingGateway $gateway;
    private User $user;
    private Hotel $hotel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new HotelBookingGateway();
        $this->user = User::factory()->create();
        $this->hotel = Hotel::create([
            'name' => 'Coral Bay Resort',
            'description' => 'Beachside hotel',
            'address' => 'North Shore, Picnic Island',
            'star_rating' => 5,
        ]);
    }

    public function test_valid_bookings_for_returns_confirmed_bookings_in_date_range(): void
    {
        $booking = HotelBooking::create([
            'user_id' => $this->user->id,
            'hotel_id' => $this->hotel->id,
            'reference' => 'PIB-HB-000001',
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-15',
            'guests' => 2,
            'total_amount' => 500.00,
            'status' => 'confirmed',
        ]);

        $valid = $this->gateway->validBookingsFor($this->user, '2026-09-12');
        $this->assertCount(1, $valid);
        $this->assertEquals($booking->id, $valid->first()->id);
        $this->assertTrue($this->gateway->hasValidBookingFor($this->user, '2026-09-12'));
    }

    public function test_valid_bookings_for_excludes_pending_or_cancelled_bookings(): void
    {
        HotelBooking::create([
            'user_id' => $this->user->id,
            'hotel_id' => $this->hotel->id,
            'reference' => 'PIB-HB-000002',
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-15',
            'guests' => 1,
            'total_amount' => 300.00,
            'status' => 'pending',
        ]);

        $valid = $this->gateway->validBookingsFor($this->user, '2026-09-12');
        $this->assertCount(0, $valid);
        $this->assertFalse($this->gateway->hasValidBookingFor($this->user, '2026-09-12'));
    }

    public function test_valid_bookings_for_excludes_bookings_outside_stay_dates(): void
    {
        HotelBooking::create([
            'user_id' => $this->user->id,
            'hotel_id' => $this->hotel->id,
            'reference' => 'PIB-HB-000003',
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-15',
            'guests' => 1,
            'total_amount' => 300.00,
            'status' => 'confirmed',
        ]);

        $valid = $this->gateway->validBookingsFor($this->user, '2026-09-18');
        $this->assertCount(0, $valid);
    }

    public function test_assert_authorises_succeeds_for_valid_booking(): void
    {
        $booking = HotelBooking::create([
            'user_id' => $this->user->id,
            'hotel_id' => $this->hotel->id,
            'reference' => 'PIB-HB-000004',
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-15',
            'guests' => 2,
            'total_amount' => 500.00,
            'status' => 'confirmed',
        ]);

        $authorised = $this->gateway->assertAuthorises($booking->id, $this->user, '2026-09-12');
        $this->assertEquals($booking->id, $authorised->id);
    }

    public function test_assert_authorises_throws_if_date_is_outside_stay(): void
    {
        $this->expectException(HttpException::class);

        $booking = HotelBooking::create([
            'user_id' => $this->user->id,
            'hotel_id' => $this->hotel->id,
            'reference' => 'PIB-HB-000005',
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-15',
            'guests' => 2,
            'total_amount' => 500.00,
            'status' => 'confirmed',
        ]);

        $this->gateway->assertAuthorises($booking->id, $this->user, '2026-09-20');
    }
}
