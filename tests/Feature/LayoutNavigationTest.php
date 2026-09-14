<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_an_admin_sees_the_admin_dropdown_and_its_links(): void
    {
        $admin = User::factory()->role('admin')->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Admin');
        $response->assertSeeHtml('href="'.route('admin.dashboard').'"');
        $response->assertSeeHtml('href="'.route('admin.users.index').'"');
    }

    public function test_a_visitor_sees_my_bookings_but_not_the_admin_dropdown(): void
    {
        $visitor = User::factory()->role('visitor')->create();

        $response = $this->actingAs($visitor)->get(route('visitor.bookings.index'));

        $response->assertOk();
        $response->assertSeeHtml('href="'.route('visitor.bookings.index').'"');
        $response->assertDontSeeHtml('href="'.route('admin.dashboard').'"');
        $response->assertDontSeeHtml('href="'.route('admin.users.index').'"');
    }

    public function test_a_guest_sees_authentication_links_but_no_role_links(): void
    {
        $response = $this->get(route('hotel.index'));

        $response->assertOk();
        $response->assertDontSeeHtml('href="'.route('visitor.bookings.index').'"');
        $response->assertDontSeeHtml('href="'.route('admin.dashboard').'"');
        $response->assertDontSeeHtml('href="'.route('admin.users.index').'"');
        $response->assertSeeHtml('href="'.route('login').'"');
        $response->assertSeeHtml('href="'.route('register').'"');
    }

    public function test_the_layout_references_the_vendored_bootstrap_assets(): void
    {
        $response = $this->get(route('hotel.index'));

        $response->assertOk();
        $response->assertSee('css/bootstrap.min.css');
        $response->assertSee('js/bootstrap.bundle.min.js');
        $response->assertDontSee('cdn.jsdelivr.net');
        $this->assertFileExists(public_path('css/bootstrap.min.css'));
        $this->assertFileExists(public_path('js/bootstrap.bundle.min.js'));
    }
}
