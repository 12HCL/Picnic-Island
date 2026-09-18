<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\AdminDemoSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_demo_admin_is_active_and_can_authenticate(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(AdminDemoSeeder::class);

        $admin = User::query()->where('email', 'admin@picnic.test')->firstOrFail();

        $this->assertTrue($admin->is_active);
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue(Hash::check('password', $admin->password));
    }

    public function test_the_demo_admin_seeder_is_safe_to_run_more_than_once(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(AdminDemoSeeder::class);
        $this->seed(AdminDemoSeeder::class);

        $this->assertSame(
            1,
            User::query()->where('email', 'admin@picnic.test')->count(),
        );
    }
}
