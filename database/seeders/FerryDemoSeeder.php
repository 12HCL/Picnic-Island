<?php

namespace Database\Seeders;

use App\Models\FerryRoute;
use App\Models\FerrySchedule;
use App\Models\Hotel;
use App\Models\HotelBooking;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Module 3 — Ferry. Owner: Ali Naayif.
 *
 * Demonstration data for the BR-01 walkthrough, and for the two screenshots the testing
 * chapter needs: a visitor who may travel and a visitor who may not.
 *
 * Called by DatabaseSeeder on migrate --seed. It can also be run on its own:
 *     php artisan db:seed --class=FerryDemoSeeder
 *
 * Every row is keyed with updateOrCreate or firstOrCreate, so running it twice updates
 * rather than duplicates. It creates its own demo accounts and does not modify any
 * existing user.
 *
 * NOTE: the hotel and hotel_bookings rows are Raafil's tables. They exist here only so the
 * ferry page has something to authorise against locally; they are demo data, not his seeder.
 */
class FerryDemoSeeder extends Seeder
{
    /**
     * Demo accounts. Local only — these passwords are not used anywhere else.
     */
    private const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        $visitorRoleId = Role::where('name', 'visitor')->value('id');

        // The sailing everything else hangs off.
        $route = FerryRoute::updateOrCreate(
            ['origin' => 'Mainland Jetty', 'destination' => 'North Jetty'],
            ['duration_minutes' => 25, 'base_fare' => 45.00],
        );

        $vessel = Vessel::updateOrCreate(
            ['name' => 'MV Coral Queen'],
            ['capacity' => 120, 'status' => 'active'],
        );

        $sailingDate = now()->addDays(3)->toDateString();

        $schedule = FerrySchedule::updateOrCreate(
            [
                'ferry_route_id' => $route->id,
                'departure_date' => $sailingDate,
                'departure_time' => '09:30:00',
            ],
            [
                'vessel_id' => $vessel->id,
                'status' => 'scheduled',
            ],
        );

        // Raafil's tables — demo rows only, so BR-01 has something real to check.
        $hotel = Hotel::updateOrCreate(
            ['name' => 'Palm Reef Hotel'],
            [
                'description' => 'The island hotel, on the western shore above Coral Beach.',
                'address' => 'Coral Beach Road, Picnic Island',
                'star_rating' => 4,
            ],
        );

        // Visitor A — holds a confirmed booking covering the sailing date. May travel.
        $allowed = User::updateOrCreate(
            ['email' => 'demo.allowed@example.com'],
            [
                'name' => 'Aisha Allowed',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'role_id' => $visitorRoleId,
                'is_active' => true,
            ],
        );

        // Four nights in Standard Room 101 at MVR 850, so the total matches the room.
        $booking = HotelBooking::updateOrCreate(
            ['reference' => 'PIB-HB-DEMO01'],
            [
                'user_id' => $allowed->id,
                'hotel_id' => $hotel->id,
                'check_in' => now()->addDays(2)->toDateString(),
                'check_out' => now()->addDays(6)->toDateString(),
                'guests' => 2,
                'total_amount' => 3400.00,
                'status' => 'confirmed',
            ],
        );

        // Room 101 comes from HotelDemoSeeder. Run on its own, this seeder skips the room
        // rather than failing, because BR-01 only needs the booking.
        $room = Room::where('hotel_id', $hotel->id)->where('room_number', '101')->first();

        if ($room) {
            $booking->rooms()->syncWithoutDetaching([
                $room->id => ['nightly_rate' => 850.00, 'nights' => 4],
            ]);
        }

        // Visitor B — no hotel booking at all. Must be refused.
        User::updateOrCreate(
            ['email' => 'demo.blocked@example.com'],
            [
                'name' => 'Bilal Blocked',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'role_id' => $visitorRoleId,
                'is_active' => true,
            ],
        );

        // A ferry operator, for the staff side: counter issuance (UC-14), boarding
        // validation, the timetable and the manifest.
        User::updateOrCreate(
            ['email' => 'demo.operator@example.com'],
            [
                'name' => 'Omar Operator',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'role_id' => Role::where('name', 'ferry_operator')->value('id'),
                'is_active' => true,
            ],
        );

        // A second sailing on the same route, so the timetable and the filters have more
        // than one row to show.
        FerrySchedule::updateOrCreate(
            [
                'ferry_route_id' => $route->id,
                'departure_date' => now()->addDays(4)->toDateString(),
                'departure_time' => '16:00:00',
            ],
            [
                'vessel_id' => $vessel->id,
                'status' => 'scheduled',
            ],
        );

        $this->command?->info('Ferry demo data ready.');
        $this->command?->info('  Sailing:  '.$sailingDate.' 09:30, schedule id '.$schedule->id);
        $this->command?->info('  Booking page: /ferry/schedules/'.$schedule->id.'/book');
        $this->command?->info('  Allowed:  demo.allowed@example.com / '.self::DEMO_PASSWORD);
        $this->command?->info('  Blocked:  demo.blocked@example.com / '.self::DEMO_PASSWORD);
        $this->command?->info('  Operator: demo.operator@example.com / '.self::DEMO_PASSWORD);
    }
}
