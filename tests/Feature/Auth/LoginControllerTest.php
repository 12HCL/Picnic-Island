<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_form(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertViewIs('auth.login');
    }

    public function test_user_can_log_in_with_correct_credentials(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create([
            'email' => 'visitor@example.com',
            'password' => 'password123',
        ]);

        $response = $this->post('/login', [
            'email' => 'visitor@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_fails_without_authenticating_user(): void
    {
        $this->seed(RoleSeeder::class);
        User::factory()->create([
            'email' => 'visitor@example.com',
            'password' => 'password123',
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'visitor@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'These credentials do not match our records.',
        ]);
        $this->assertGuest();
    }

    public function test_unknown_email_fails_with_the_same_validation_error(): void
    {
        $this->seed(RoleSeeder::class);

        $response = $this->from('/login')->post('/login', [
            'email' => 'missing@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'These credentials do not match our records.',
        ]);
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $this->seed(RoleSeeder::class);
        User::factory()->create([
            'email' => 'throttle@example.com',
            'password' => 'password123',
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', [
                'email' => 'throttle@example.com',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $response = $this->post('/login', [
            'email' => 'throttle@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'Too many login attempts. Please try again in 60 seconds.',
        ]);
        $this->assertGuest();
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->role('hotel_staff')->create([
            'email' => 'retired.staff@example.com',
            'password' => 'password123',
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivated_user_sees_the_same_error_as_an_unknown_email(): void
    {
        $this->seed(RoleSeeder::class);

        User::factory()->role('hotel_staff')->create([
            'email' => 'deactivated@example.com',
            'password' => 'password123',
            'is_active' => false,
        ]);

        $expected = 'These credentials do not match our records.';

        // Both must produce the identical message, or the form confirms which
        // accounts exist and which have merely been switched off.
        $this->post('/login', [
            'email' => 'deactivated@example.com',
            'password' => 'password123',
        ])->assertSessionHasErrors(['email' => $expected]);

        $this->post('/login', [
            'email' => 'nobody@example.com',
            'password' => 'password123',
        ])->assertSessionHasErrors(['email' => $expected]);
    }

    public function test_user_can_log_out(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }
}
