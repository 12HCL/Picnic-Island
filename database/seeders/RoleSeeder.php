<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Insert the five fixed system roles.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'visitor',
                'label' => 'Visitor',
                'description' => 'Books hotels, ferry travel, and park activities',
            ],
            [
                'name' => 'hotel_staff',
                'label' => 'Hotel Staff',
                'description' => 'Manages hotels, rooms, and hotel bookings',
            ],
            [
                'name' => 'ferry_operator',
                'label' => 'Ferry Operator',
                'description' => 'Manages ferry routes, schedules, and tickets',
            ],
            [
                'name' => 'park_staff',
                'label' => 'Theme Park Staff',
                'description' => 'Manages park activities, events, and tickets',
            ],
            [
                'name' => 'admin',
                'label' => 'Administrator',
                'description' => 'Manages users and system administration',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role,
            );
        }
    }
}
