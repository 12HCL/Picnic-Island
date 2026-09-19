<?php

namespace Tests\Feature;

use App\Models\FerrySchedule;
use App\Models\HotelBooking;
use App\Models\ParkEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A fresh clone is set up with `migrate --seed`. Before the ferry and park demo seeders were
 * called from DatabaseSeeder, that left both modules empty and without staff logins.
 */
class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_gives_every_role_a_demo_account_and_every_module_data(): void
    {
        $this->seed();

        foreach ([
            'admin@picnic.test' => 'admin',
            'hotel.staff@picnic.test' => 'hotel_staff',
            'demo.operator@example.com' => 'ferry_operator',
            'park.staff@picnic.test' => 'park_staff',
            'demo.allowed@example.com' => 'visitor',
        ] as $email => $role) {
            $this->assertSame($role, User::where('email', $email)->first()?->role->name, $email);
        }

        $this->assertGreaterThan(0, FerrySchedule::count());
        $this->assertGreaterThan(0, ParkEvent::count());

        // The BR-01 demo booking has a room, so it is not a blank row on the hotel screens.
        $this->assertSame('101', HotelBooking::where('reference', 'PIB-HB-DEMO01')->first()->rooms->first()?->room_number);
    }
}
