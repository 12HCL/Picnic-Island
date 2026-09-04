<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Covers the 'role' middleware alias every module uses to protect its staff
 * routes - BUILD_CONTRACT.md section 6, seam 2.
 *
 * The routes below are defined here rather than in routes/modules/*.php so
 * this test does not depend on anyone else's module being written yet.
 */
class EnsureUserHasRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'role:admin'])
            ->get('/test-admin-only', fn () => 'reached');

        Route::middleware(['web', 'role:admin,park_staff'])
            ->get('/test-either-role', fn () => 'reached');
    }

    public function test_a_user_with_the_required_role_is_allowed_through(): void
    {
        $admin = User::factory()->role('admin')->create();

        $this->actingAs($admin)
            ->get('/test-admin-only')
            ->assertOk()
            ->assertSee('reached');
    }

    public function test_a_user_with_the_wrong_role_is_refused(): void
    {
        $visitor = User::factory()->role('visitor')->create();

        $this->actingAs($visitor)
            ->get('/test-admin-only')
            ->assertForbidden();
    }

    public function test_a_guest_is_refused(): void
    {
        $this->get('/test-admin-only')->assertUnauthorized();
    }

    public function test_any_one_of_several_named_roles_is_enough(): void
    {
        $parkStaff = User::factory()->role('park_staff')->create();

        $this->actingAs($parkStaff)
            ->get('/test-either-role')
            ->assertOk();
    }

    public function test_a_role_not_in_the_list_is_still_refused(): void
    {
        $ferryOperator = User::factory()->role('ferry_operator')->create();

        $this->actingAs($ferryOperator)
            ->get('/test-either-role')
            ->assertForbidden();
    }

    public function test_the_factory_defaults_a_user_to_the_visitor_role(): void
    {
        $this->assertTrue(User::factory()->create()->hasRole('visitor'));
    }
}
