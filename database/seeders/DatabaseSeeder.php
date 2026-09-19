<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        // Module 1 (Faain): a fresh clone needs an administrator who can open the admin
        // dashboard, users, reports, promotions, and map-location management during the demo.
        $this->call(AdminDemoSeeder::class);

        // Module 5 (Safhaan): without this the map renders with no clickable points on
        // a fresh clone. MapLocationSeeder uses updateOrCreate, so it is safe to re-run.
        $this->call(MapLocationSeeder::class);

        // Module 2 (Raafil): seeds Palm Reef Hotel, room types, physical rooms, and staff demo account.
        $this->call(HotelDemoSeeder::class);

        // Module 3 (Naayif): sailings, the BR-01 allowed/blocked visitors and a ferry operator.
        // Runs after the hotel seeder because it reuses Palm Reef Hotel for the demo booking.
        $this->call(FerryDemoSeeder::class);

        // Module 4 (Malaaz): park activities, events, sales and a park staff account.
        $this->call(ParkDemoSeeder::class);

        // Module 5 (Safhaan): offers for the front page and the promotions screens. Runs last
        // because each promotion is attributed to the staff member the earlier seeders create.
        $this->call(PromotionDemoSeeder::class);

        $visitorRole = Role::where('name', 'visitor')->firstOrFail();

        User::factory()->create([
            'role_id' => $visitorRole->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
