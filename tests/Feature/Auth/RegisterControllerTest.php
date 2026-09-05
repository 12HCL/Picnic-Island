<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_registration_form(): void
    {
        $response = $this->get('/register');

        $response->assertOk();
        $response->assertViewIs('auth.register');
    }

    public function test_guest_can_register_as_visitor(): void
    {
        $this->seed(RoleSeeder::class);

        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $visitorRole = Role::where('name', 'visitor')->firstOrFail();

        $response = $this->post('/register', [
            'name' => 'Example Visitor',
            'email' => 'visitor@example.com',
            'phone' => '7771234',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $adminRole->id,
        ]);

        $user = User::where('email', 'visitor@example.com')->firstOrFail();

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame($visitorRole->id, $user->role_id);
        $this->assertSame('7771234', $user->phone);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_registration_rejects_duplicate_email_and_mismatched_password(): void
    {
        $this->seed(RoleSeeder::class);
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->post('/register', [
            'name' => 'Another Visitor',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }
}
