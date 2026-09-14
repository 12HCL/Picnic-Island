<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_an_admin_sees_the_user_list_and_its_contract_data(): void
    {
        $admin = User::factory()->role('admin')->create();
        $inactiveVisitor = User::factory()->role('visitor')->create([
            'name' => 'Inactive Visitor',
            'email' => 'inactive@example.com',
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertViewIs('admin.users.index');
        $response->assertViewHasAll(['users', 'roles', 'filters']);
        $response->assertViewHas('users', function ($users) use ($inactiveVisitor): bool {
            if (! $users instanceof LengthAwarePaginator) {
                return false;
            }

            $user = $users->getCollection()->firstWhere('id', $inactiveVisitor->id);

            return $user instanceof User
                && $user->relationLoaded('role')
                && $user->is_active === false;
        });
        $response->assertViewHas('roles', fn ($roles): bool => $roles instanceof Collection);
        $response->assertViewHas('filters', [
            'search' => null,
            'role' => null,
        ]);
        $response->assertSee('Inactive Visitor');
        $response->assertSee('Inactive');
    }

    public function test_search_filters_users_by_partial_name_or_email(): void
    {
        $admin = User::factory()->role('admin')->create();
        $nameMatch = User::factory()->role('visitor')->create([
            'name' => 'Needle Name',
            'email' => 'first@example.com',
        ]);
        $emailMatch = User::factory()->role('visitor')->create([
            'name' => 'Second Match',
            'email' => 'needle@example.com',
        ]);
        $nonMatch = User::factory()->role('visitor')->create([
            'name' => 'Different User',
            'email' => 'different@example.com',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'search' => 'needle',
        ]));

        $response->assertOk();
        $response->assertSee($nameMatch->email);
        $response->assertSee($emailMatch->email);
        $response->assertDontSee($nonMatch->email);
        $response->assertViewHas('users', fn (LengthAwarePaginator $users): bool => $users->total() === 2);
    }

    public function test_role_filter_narrows_the_user_list(): void
    {
        $admin = User::factory()->role('admin')->create();
        $hotelStaff = User::factory()->role('hotel_staff')->create([
            'email' => 'hotel.staff@example.com',
        ]);
        $visitor = User::factory()->role('visitor')->create([
            'email' => 'visitor@example.com',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'role' => 'hotel_staff',
        ]));

        $response->assertOk();
        $response->assertSee($hotelStaff->email);
        $response->assertDontSee($visitor->email);
        $response->assertViewHas('users', fn (LengthAwarePaginator $users): bool => $users->total() === 1);
    }

    public function test_filters_combine_and_survive_on_page_two(): void
    {
        $admin = User::factory()->role('admin')->create();

        for ($number = 1; $number <= 16; $number++) {
            User::factory()->role('visitor')->create([
                'name' => sprintf('Filterable User %02d', $number),
                'email' => sprintf('filterable%02d@example.com', $number),
            ]);
        }

        $wrongRole = User::factory()->role('hotel_staff')->create([
            'name' => 'Filterable Wrong Role',
            'email' => 'wrong-role@example.com',
        ]);

        $filters = [
            'search' => 'Filterable',
            'role' => 'visitor',
        ];

        $firstPage = $this->actingAs($admin)->get(route('admin.users.index', $filters));

        $firstPage->assertOk();
        $firstPage->assertDontSee($wrongRole->email);
        $firstPage->assertViewHas('users', function (LengthAwarePaginator $users) use ($filters): bool {
            return $users->total() === 16
                && $this->urlHasQuery($users->nextPageUrl(), [...$filters, 'page' => '2']);
        });

        $secondPage = $this->actingAs($admin)->get(route('admin.users.index', [
            ...$filters,
            'page' => 2,
        ]));

        $secondPage->assertOk();
        $secondPage->assertSee('filterable16@example.com');
        $secondPage->assertViewHas('filters', $filters);
        $secondPage->assertViewHas('users', function (LengthAwarePaginator $users) use ($filters): bool {
            return $users->currentPage() === 2
                && $users->count() === 1
                && $this->urlHasQuery($users->previousPageUrl(), [...$filters, 'page' => '1']);
        });
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }

    public function test_a_visitor_cannot_open_the_admin_user_list(): void
    {
        $visitor = User::factory()->role('visitor')->create();

        $this->actingAs($visitor)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_an_admin_creates_a_staff_account_with_a_chosen_role(): void
    {
        $admin = User::factory()->role('admin')->create();
        $hotelStaffRole = $this->role('hotel_staff');

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Hotel Employee',
            'email' => 'new.staff@example.com',
            'phone' => '7771234',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'role_id' => $hotelStaffRole->id,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $user = User::query()->where('email', 'new.staff@example.com')->firstOrFail();

        $this->assertSame($hotelStaffRole->id, $user->role_id);
        $this->assertSame('7771234', $user->phone);
        $this->assertTrue(Hash::check('secure-password', $user->password));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Hotel Employee',
            'role_id' => $hotelStaffRole->id,
        ]);
    }

    public function test_role_id_cannot_be_escalated_through_mass_assignment(): void
    {
        $admin = User::factory()->role('admin')->create();
        $unofferedRoleId = Role::query()->max('id') + 1000;

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Invalid Role User',
            'email' => 'invalid-role@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'role_id' => $unofferedRoleId,
        ]);

        $response->assertSessionHasErrors('role_id');
        $this->assertDatabaseMissing('users', ['email' => 'invalid-role@example.com']);
    }

    public function test_a_duplicate_email_fails_validation_and_repopulates_the_form(): void
    {
        $admin = User::factory()->role('admin')->create();
        $visitorRole = $this->role('visitor');
        User::factory()->role('visitor')->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($admin)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => 'Duplicate Email User',
                'email' => 'existing@example.com',
                'phone' => '7654321',
                'password' => 'secure-password',
                'password_confirmation' => 'secure-password',
                'role_id' => $visitorRole->id,
            ]);

        $response->assertRedirect(route('admin.users.create'));
        $response->assertSessionHasErrors([
            'email' => 'Email is already registered.',
        ]);
        $response->assertSessionHasInput('name', 'Duplicate Email User');
        $response->assertSessionHasInput('email', 'existing@example.com');
        $response->assertSessionHasInput('phone', '7654321');
        $response->assertSessionHasInput('role_id', (string) $visitorRole->id);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_an_admin_changes_another_users_role(): void
    {
        $admin = User::factory()->role('admin')->create();
        $user = User::factory()->role('visitor')->create();
        $ferryRole = $this->role('ferry_operator');

        $response = $this->actingAs($admin)->put(
            route('admin.users.update', $user),
            $this->updateData($user, $ferryRole),
        );

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');
        $this->assertSame($ferryRole->id, $user->refresh()->role_id);
    }

    public function test_updating_with_a_blank_password_preserves_the_existing_hash(): void
    {
        $admin = User::factory()->role('admin')->create();
        $user = User::factory()->role('visitor')->create();
        $originalHash = $user->password;

        $response = $this->actingAs($admin)->put(
            route('admin.users.update', $user),
            $this->updateData($user, $this->role('visitor'), [
                'password' => '',
                'password_confirmation' => '',
            ]),
        );

        $response->assertRedirect(route('admin.users.index'));
        $this->assertSame($originalHash, $user->refresh()->password);
    }

    public function test_updating_with_a_password_replaces_the_existing_hash(): void
    {
        $admin = User::factory()->role('admin')->create();
        $user = User::factory()->role('visitor')->create();
        $originalHash = $user->password;

        $response = $this->actingAs($admin)->put(
            route('admin.users.update', $user),
            $this->updateData($user, $this->role('visitor'), [
                'password' => 'replacement-password',
                'password_confirmation' => 'replacement-password',
            ]),
        );

        $response->assertRedirect(route('admin.users.index'));

        $updatedHash = $user->refresh()->password;
        $this->assertNotSame($originalHash, $updatedHash);
        $this->assertTrue(Hash::check('replacement-password', $updatedHash));
    }

    public function test_email_uniqueness_ignores_the_user_being_updated(): void
    {
        $admin = User::factory()->role('admin')->create();
        $user = User::factory()->role('visitor')->create([
            'email' => 'unchanged@example.com',
        ]);

        $response = $this->actingAs($admin)->put(
            route('admin.users.update', $user),
            $this->updateData($user, $this->role('visitor')),
        );

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHasNoErrors();
        $this->assertSame('unchanged@example.com', $user->refresh()->email);
    }

    public function test_deactivate_sets_is_active_to_false(): void
    {
        $admin = User::factory()->role('admin')->create();
        $user = User::factory()->role('hotel_staff')->create();

        $response = $this->actingAs($admin)->post(route('admin.users.deactivate', $user));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');
        $this->assertFalse($user->refresh()->is_active);
    }

    public function test_activate_sets_is_active_to_true(): void
    {
        $admin = User::factory()->role('admin')->create();
        $user = User::factory()->role('hotel_staff')->create(['is_active' => false]);

        $response = $this->actingAs($admin)->post(route('admin.users.activate', $user));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');
        $this->assertTrue($user->refresh()->is_active);
    }

    public function test_the_last_active_admin_cannot_be_deactivated(): void
    {
        $admin = User::factory()->role('admin')->create();

        $response = $this->actingAs($admin)->post(route('admin.users.deactivate', $admin));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('error', 'The last active admin cannot be deactivated.');
        $this->assertTrue($admin->refresh()->is_active);
    }

    public function test_the_last_active_admin_cannot_be_demoted_to_another_role(): void
    {
        $admin = User::factory()->role('admin')->create();
        $visitorRole = $this->role('visitor');

        $response = $this->actingAs($admin)->put(
            route('admin.users.update', $admin),
            $this->updateData($admin, $visitorRole),
        );

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('error', 'The last active admin cannot be assigned another role.');
        $this->assertSame($this->role('admin')->id, $admin->refresh()->role_id);
    }

    public function test_an_admin_can_view_user_details_and_activity_counts(): void
    {
        $admin = User::factory()->role('admin')->create();
        $user = User::factory()->role('hotel_staff')->create([
            'name' => 'Details User',
            'email' => 'details@example.com',
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.show', $user));

        $response->assertOk();
        $response->assertViewIs('admin.users.show');
        $response->assertViewHas('user', function (User $viewUser) use ($user): bool {
            return $viewUser->is($user)
                && $viewUser->relationLoaded('role')
                && $viewUser->is_active === false;
        });
        $response->assertViewHas('activityCounts', [
            'hotel_bookings' => 0,
            'ferry_tickets' => 0,
            'payments' => 0,
        ]);
        $response->assertSee('Details User');
        $response->assertSee('Hotel Staff');
        $response->assertSee('Inactive');
    }

    public function test_a_guest_is_redirected_from_every_new_user_route(): void
    {
        $user = User::factory()->role('visitor')->create();

        foreach ($this->newRouteRequests($user) as [$method, $uri]) {
            $this->call($method, $uri)->assertRedirect(route('login'));
        }
    }

    public function test_a_non_admin_is_forbidden_from_every_new_user_route(): void
    {
        $visitor = User::factory()->role('visitor')->create();
        $user = User::factory()->role('hotel_staff')->create();

        $this->actingAs($visitor);

        foreach ($this->newRouteRequests($user) as [$method, $uri]) {
            $this->call($method, $uri)->assertForbidden();
        }
    }

    /**
     * @param  array<string, string>  $expected
     */
    private function urlHasQuery(?string $url, array $expected): bool
    {
        if ($url === null) {
            return false;
        }

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return array_intersect_key($query, $expected) === $expected;
    }

    private function role(string $name): Role
    {
        return Role::query()->where('name', $name)->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function updateData(User $user, Role $role, array $overrides = []): array
    {
        return array_merge([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'password' => '',
            'password_confirmation' => '',
            'role_id' => $role->id,
        ], $overrides);
    }

    /**
     * @return array<int, array{string, string}>
     */
    private function newRouteRequests(User $user): array
    {
        return [
            ['GET', route('admin.users.create')],
            ['POST', route('admin.users.store')],
            ['GET', route('admin.users.show', $user)],
            ['GET', route('admin.users.edit', $user)],
            ['PUT', route('admin.users.update', $user)],
            ['POST', route('admin.users.deactivate', $user)],
            ['POST', route('admin.users.activate', $user)],
        ];
    }
}
