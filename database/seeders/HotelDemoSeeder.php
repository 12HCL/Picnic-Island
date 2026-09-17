<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\HotelBooking;
use App\Models\HotelBookingRoom;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Module 2 — Hotel. Owner: Ahmed Raafil.
 *
 * Demonstration data for hotel accommodations, room types, and physical rooms.
 * Populates Palm Reef Hotel (matching the island map landmark) and Azure Sands Resort,
 * along with demo accounts and initial booking records for staff and reporting demos.
 *
 * Run via:
 *     php artisan db:seed --class=HotelDemoSeeder
 */
class HotelDemoSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        $hotelStaffRoleId = Role::where('name', 'hotel_staff')->value('id');
        $visitorRoleId    = Role::where('name', 'visitor')->value('id');

        // ---------------------------------------------------------------------
        // 1. Demo Users
        // ---------------------------------------------------------------------
        $staffUser = User::firstOrCreate(
            ['email' => 'hotel.staff@picnic.test'],
            [
                'name'     => 'Aishath Nuha (Hotel Staff)',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'role_id'  => $hotelStaffRoleId,
                'phone'    => '7700001',
            ]
        );

        $visitorUser = User::firstOrCreate(
            ['email' => 'hotel.visitor@picnic.test'],
            [
                'name'     => 'Hassan Zayan (Visitor)',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'role_id'  => $visitorRoleId,
                'phone'    => '7700002',
            ]
        );

        // ---------------------------------------------------------------------
        // 2. Hotels (Matches map landmark in MapLocationSeeder)
        // ---------------------------------------------------------------------
        $palmReef = Hotel::updateOrCreate(
            ['name' => 'Palm Reef Hotel'],
            [
                'description' => 'The premier island hotel with ocean views, beachfront dining, and direct access to pristine coral reefs. Check-in from 14:00.',
                'address'     => 'Central Beach, Picnic Island',
                'star_rating' => 4,
            ]
        );

        $azureSands = Hotel::updateOrCreate(
            ['name' => 'Azure Sands Resort'],
            [
                'description' => 'Luxury 5-star overwater resort on the sheltered western lagoon, offering sunset villas and personalized island hospitality.',
                'address'     => 'West Lagoon Drive, Picnic Island',
                'star_rating' => 5,
            ]
        );

        // ---------------------------------------------------------------------
        // 3. Room Types
        // ---------------------------------------------------------------------
        // Palm Reef Hotel types
        $prStandard = RoomType::updateOrCreate(
            ['hotel_id' => $palmReef->id, 'name' => 'Standard Room'],
            [
                'description'   => 'Comfortable room with a queen bed, air conditioning, en-suite bathroom, and garden terrace.',
                'base_price'    => 850.00,
                'max_occupancy' => 2,
            ]
        );

        $prDeluxe = RoomType::updateOrCreate(
            ['hotel_id' => $palmReef->id, 'name' => 'Deluxe Sea View'],
            [
                'description'   => 'Spacious room with king-size bed, private balcony with direct ocean panorama, and mini-bar.',
                'base_price'    => 1400.00,
                'max_occupancy' => 3,
            ]
        );

        $prVilla = RoomType::updateOrCreate(
            ['hotel_id' => $palmReef->id, 'name' => 'Beach Villa'],
            [
                'description'   => 'Exclusive private villa situated directly on the white sand beach, featuring an outdoor rain shower and plunge pool.',
                'base_price'    => 2600.00,
                'max_occupancy' => 4,
            ]
        );

        // Azure Sands Resort types
        $asSuite = RoomType::updateOrCreate(
            ['hotel_id' => $azureSands->id, 'name' => 'Overwater Lagoon Suite'],
            [
                'description'   => 'Iconic overwater bungalow with glass floor viewing panels, sun deck, and direct lagoon ladder access.',
                'base_price'    => 2100.00,
                'max_occupancy' => 2,
            ]
        );

        $asSunsetVilla = RoomType::updateOrCreate(
            ['hotel_id' => $azureSands->id, 'name' => 'Sunset Family Villa'],
            [
                'description'   => 'Two-bedroom luxury villa with infinity pool, living pavilion, and unobstructed sunset views.',
                'base_price'    => 3800.00,
                'max_occupancy' => 6,
            ]
        );

        // ---------------------------------------------------------------------
        // 4. Physical Rooms
        // ---------------------------------------------------------------------
        $roomsData = [
            // Palm Reef
            ['hotel_id' => $palmReef->id, 'room_type_id' => $prStandard->id, 'room_number' => '101', 'floor' => 1, 'status' => 'available'],
            ['hotel_id' => $palmReef->id, 'room_type_id' => $prStandard->id, 'room_number' => '102', 'floor' => 1, 'status' => 'available'],
            ['hotel_id' => $palmReef->id, 'room_type_id' => $prStandard->id, 'room_number' => '103', 'floor' => 1, 'status' => 'available'],
            ['hotel_id' => $palmReef->id, 'room_type_id' => $prStandard->id, 'room_number' => '104', 'floor' => 1, 'status' => 'maintenance'],
            ['hotel_id' => $palmReef->id, 'room_type_id' => $prDeluxe->id,   'room_number' => '201', 'floor' => 2, 'status' => 'available'],
            ['hotel_id' => $palmReef->id, 'room_type_id' => $prDeluxe->id,   'room_number' => '202', 'floor' => 2, 'status' => 'available'],
            ['hotel_id' => $palmReef->id, 'room_type_id' => $prDeluxe->id,   'room_number' => '203', 'floor' => 2, 'status' => 'available'],
            ['hotel_id' => $palmReef->id, 'room_type_id' => $prVilla->id,    'room_number' => 'V-01', 'floor' => 1, 'status' => 'available'],
            ['hotel_id' => $palmReef->id, 'room_type_id' => $prVilla->id,    'room_number' => 'V-02', 'floor' => 1, 'status' => 'available'],

            // Azure Sands
            ['hotel_id' => $azureSands->id, 'room_type_id' => $asSuite->id,       'room_number' => '301', 'floor' => 1, 'status' => 'available'],
            ['hotel_id' => $azureSands->id, 'room_type_id' => $asSuite->id,       'room_number' => '302', 'floor' => 1, 'status' => 'available'],
            ['hotel_id' => $azureSands->id, 'room_type_id' => $asSuite->id,       'room_number' => '303', 'floor' => 1, 'status' => 'available'],
            ['hotel_id' => $azureSands->id, 'room_type_id' => $asSunsetVilla->id, 'room_number' => 'V-10', 'floor' => 1, 'status' => 'available'],
            ['hotel_id' => $azureSands->id, 'room_type_id' => $asSunsetVilla->id, 'room_number' => 'V-11', 'floor' => 1, 'status' => 'available'],
        ];

        $rooms = [];
        foreach ($roomsData as $data) {
            $rooms[$data['room_number']] = Room::updateOrCreate(
                ['hotel_id' => $data['hotel_id'], 'room_number' => $data['room_number']],
                [
                    'room_type_id' => $data['room_type_id'],
                    'floor'        => $data['floor'],
                    'status'       => $data['status'],
                ]
            );
        }

        // ---------------------------------------------------------------------
        // 5. Initial Demo Bookings (To populate dashboard & reports)
        // ---------------------------------------------------------------------
        $checkIn  = now()->addDays(2)->toDateString();
        $checkOut = now()->addDays(5)->toDateString();

        $booking = HotelBooking::firstOrCreate(
            ['reference' => 'PIB-HB-000001'],
            [
                'user_id'      => $visitorUser->id,
                'hotel_id'     => $palmReef->id,
                'check_in'     => $checkIn,
                'check_out'    => $checkOut,
                'guests'       => 2,
                'total_amount' => 4200.00,
                'status'       => 'confirmed',
            ]
        );

        if (isset($rooms['201'])) {
            HotelBookingRoom::firstOrCreate(
                [
                    'hotel_booking_id' => $booking->id,
                    'room_id'          => $rooms['201']->id,
                ],
                [
                    'nightly_rate' => 1400.00,
                    'nights'       => 3,
                ]
            );
        }

        $this->command?->info("Hotel demo data seeded successfully.");
        $this->command?->info("  Hotel Staff: hotel.staff@picnic.test / password");
        $this->command?->info("  Visitor:     hotel.visitor@picnic.test / password");
        $this->command?->info("  Hotels:      Palm Reef Hotel, Azure Sands Resort");
    }
}
