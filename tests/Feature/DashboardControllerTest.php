<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_role_can_log_in_and_reach_dashboard_fallback(): void
    {
        $this->seed(RoleSeeder::class);

        $roleNames = [
            'visitor',
            'hotel_staff',
            'ferry_operator',
            'park_staff',
            'admin',
        ];

        foreach ($roleNames as $roleName) {
            $user = User::factory()->role($roleName)->create([
                'email' => $roleName.'@example.com',
                'password' => 'password123',
            ]);

            $loginResponse = $this->post('/login', [
                'email' => $user->email,
                'password' => 'password123',
            ]);

            $loginResponse->assertRedirect(route('dashboard'));
            $this->assertAuthenticatedAs($user);

            $dashboardResponse = $this->get('/dashboard');
            $dashboardResponse->assertOk();
            $dashboardResponse->assertViewIs('dashboard');

            $this->post('/logout');
            $this->assertGuest();
        }
    }

    public function test_dashboard_redirects_guest_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }
}
