<?php

namespace Tests\Feature\Ferry;

use App\Models\FerryRoute;
use App\Models\FerrySchedule;
use App\Models\Hotel;
use App\Models\HotelBooking;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Module 3 — Ferry. Who may open the visitor booking page.
 *
 * QA finding #3 (00_Admin/bugs/2026-09-12-end-to-end-qa.md) recorded the route carrying
 * 'auth' alone, so every authenticated role reached it. role:visitor was added on
 * 18 September; these tests are what stops it being dropped again.
 *
 * Role middleware answers "who is this page for". BR-01 — whether this particular visitor
 * may travel — stays in the controller, and is covered separately below.
 */
class FerryTicketAccessTest extends TestCase
{
    use RefreshDatabase;

    private function schedule(): FerrySchedule
    {
        $route = FerryRoute::create([
            'origin' => 'Mainland Jetty',
            'destination' => 'North Jetty',
            'duration_minutes' => 25,
            'base_fare' => 45.00,
        ]);

        $vessel = Vessel::create([
            'name' => 'MV Coral Queen',
            'capacity' => 120,
            'status' => 'active',
        ]);

        return FerrySchedule::create([
            'ferry_route_id' => $route->id,
            'vessel_id' => $vessel->id,
            'departure_date' => now()->addDays(3)->toDateString(),
            'departure_time' => '09:30:00',
        ]);
    }

    private function url(FerrySchedule $schedule): string
    {
        return '/ferry/schedules/'.$schedule->id.'/book';
    }

    public function test_a_visitor_may_open_the_booking_page(): void
    {
        $schedule = $this->schedule();

        $this->actingAs(User::factory()->role('visitor')->create())
            ->get($this->url($schedule))
            ->assertOk();
    }

    /**
     * QA finding #3: these four roles all returned 200 before role:visitor was added.
     */
    public function test_every_other_role_is_refused(): void
    {
        $schedule = $this->schedule();

        foreach (['hotel_staff', 'ferry_operator', 'park_staff', 'admin'] as $role) {
            $this->actingAs(User::factory()->role($role)->create())
                ->get($this->url($schedule))
                ->assertForbidden();
        }
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $schedule = $this->schedule();

        $this->get($this->url($schedule))->assertRedirect('/login');
    }

    /**
     * BR-01, still enforced in the controller and not by the middleware: a visitor with no
     * qualifying hotel booking reaches the page and is refused on it.
     */
    public function test_a_visitor_without_a_hotel_booking_sees_the_refusal_and_no_form(): void
    {
        $schedule = $this->schedule();

        $this->actingAs(User::factory()->role('visitor')->create())
            ->get($this->url($schedule))
            ->assertOk()
            ->assertSee('A hotel booking is required')
            ->assertDontSee('name="hotel_booking_id"', false);
    }

    public function test_a_visitor_with_a_confirmed_booking_sees_the_form(): void
    {
        $schedule = $this->schedule();
        $visitor = User::factory()->role('visitor')->create();

        $hotel = Hotel::create([
            'name' => 'Palm Reef Hotel',
            'star_rating' => 4,
        ]);

        HotelBooking::create([
            'user_id' => $visitor->id,
            'hotel_id' => $hotel->id,
            'reference' => 'PIB-HB-TEST01',
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(6)->toDateString(),
            'guests' => 2,
            'total_amount' => 780.00,
            'status' => 'confirmed',
        ]);

        $this->actingAs($visitor)
            ->get($this->url($schedule))
            ->assertOk()
            ->assertSee('name="hotel_booking_id"', false)
            ->assertSee('PIB-HB-TEST01');
    }
}
