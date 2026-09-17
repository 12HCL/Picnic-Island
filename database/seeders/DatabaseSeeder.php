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

        // Module 5 (Safhaan): without this the map renders with no clickable points on
        // a fresh clone. MapLocationSeeder uses updateOrCreate, so it is safe to re-run.
        $this->call(MapLocationSeeder::class);

        // Module 2 (Raafil): seeds Palm Reef Hotel, room types, physical rooms, and staff demo account.
        $this->call(HotelDemoSeeder::class);

        $visitorRole = Role::where('name', 'visitor')->firstOrFail();

        User::factory()->create([
            'role_id' => $visitorRole->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
