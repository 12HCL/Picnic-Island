<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminDemoSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'password';

    /**
     * Create the local administrator needed to demonstrate Module 1 after a fresh setup.
     */
    public function run(): void
    {
        $adminRole = Role::query()->where('name', 'admin')->firstOrFail();

        User::query()->updateOrCreate(
            ['email' => 'admin@picnic.test'],
            [
                'role_id' => $adminRole->id,
                'name' => 'Demo Administrator',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'is_active' => true,
            ],
        );

        $this->command?->info('  Admin:       admin@picnic.test / '.self::DEMO_PASSWORD);
    }
}
