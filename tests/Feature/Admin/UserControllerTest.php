<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

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
}
