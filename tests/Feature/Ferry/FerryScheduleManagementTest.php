<?php

namespace Tests\Feature\Ferry;

use App\Models\FerryRoute;
use App\Models\FerrySchedule;
use App\Models\Hotel;
use App\Models\HotelBooking;
use App\Models\User;
use App\Models\Vessel;
use App\Services\Ferry\FerryTicketIssueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Module 3 — scheduling sailings. UC-13.
 *
 * Covers both exception flows the use case documents: E1, a sailing with passengers cannot
 * be cancelled, and E2, one vessel cannot be on two overlapping crossings.
 */
class FerryScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    private function route(int $minutes = 25): FerryRoute
    {
        return FerryRoute::create([
            'origin' => 'Mainland Jetty',
            'destination' => 'North Jetty '.uniqid(),
            'duration_minutes' => $minutes,
            'base_fare' => 45.00,
        ]);
    }

    private function vessel(string $status = 'active', int $capacity = 120): Vessel
    {
        return Vessel::create([
            'name' => 'MV '.uniqid(),
            'capacity' => $capacity,
            'status' => $status,
        ]);
    }

    private function operator(): User
    {
        return User::factory()->role('ferry_operator')->create();
    }

    public function test_an_operator_can_schedule_a_sailing(): void
    {
        $route = $this->route();
        $vessel = $this->vessel();

        $this->actingAs($this->operator())
            ->post(route('ferry.staff.schedules.store'), [
                'ferry_route_id' => $route->id,
                'vessel_id' => $vessel->id,
                'departure_date' => now()->addDays(5)->toDateString(),
                'departure_time' => '09:30',
            ])
            ->assertRedirect(route('ferry.staff.schedules.index'));

        $this->assertDatabaseHas('ferry_schedules', [
            'ferry_route_id' => $route->id,
            'vessel_id' => $vessel->id,
            'status' => 'scheduled',
            'seats_taken' => 0,
        ]);
    }

    /**
     * UC-13, assumption 2.
     */
    public function test_a_sailing_cannot_be_scheduled_in_the_past(): void
    {
        $this->actingAs($this->operator())
            ->post(route('ferry.staff.schedules.store'), [
                'ferry_route_id' => $this->route()->id,
                'vessel_id' => $this->vessel()->id,
                'departure_date' => now()->subDay()->toDateString(),
                'departure_time' => '09:30',
            ])
            ->assertSessionHasErrors('departure_date');

        $this->assertDatabaseCount('ferry_schedules', 0);
    }

    public function test_a_vessel_under_maintenance_cannot_be_assigned(): void
    {
        $this->actingAs($this->operator())
            ->post(route('ferry.staff.schedules.store'), [
                'ferry_route_id' => $this->route()->id,
                'vessel_id' => $this->vessel('maintenance')->id,
                'departure_date' => now()->addDays(5)->toDateString(),
                'departure_time' => '09:30',
            ])
            ->assertSessionHasErrors('vessel_id');
    }

    /**
     * The UNIQUE composite, surfaced as a sentence instead of a 1062 integrity error.
     */
    public function test_the_same_route_cannot_be_scheduled_twice_at_one_moment(): void
    {
        $route = $this->route();
        $date = now()->addDays(5)->toDateString();

        FerrySchedule::create([
            'ferry_route_id' => $route->id,
            'vessel_id' => $this->vessel()->id,
            'departure_date' => $date,
            'departure_time' => '09:30:00',
        ]);

        $this->actingAs($this->operator())
            ->post(route('ferry.staff.schedules.store'), [
                'ferry_route_id' => $route->id,
                'vessel_id' => $this->vessel()->id,
                'departure_date' => $date,
                'departure_time' => '09:30',
            ])
            ->assertSessionHasErrors('departure_time');

        $this->assertDatabaseCount('ferry_schedules', 1);
    }

    /**
     * UC-13 E2. The unique index covers only (route, date, time), so a vessel double-booked
     * across two different routes has no database equivalent and is checked in the
     * controller.
     */
    public function test_one_vessel_cannot_be_on_two_overlapping_crossings(): void
    {
        $vessel = $this->vessel();
        $date = now()->addDays(5)->toDateString();

        FerrySchedule::create([
            'ferry_route_id' => $this->route(60)->id,
            'vessel_id' => $vessel->id,
            'departure_date' => $date,
            'departure_time' => '09:00:00',
        ]);

        // A different route, but the same boat, leaving 30 minutes into a 60 minute crossing.
        $this->actingAs($this->operator())
            ->post(route('ferry.staff.schedules.store'), [
                'ferry_route_id' => $this->route(60)->id,
                'vessel_id' => $vessel->id,
                'departure_date' => $date,
                'departure_time' => '09:30',
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('ferry_schedules', 1);
    }

    public function test_the_same_vessel_may_sail_again_once_the_first_crossing_is_over(): void
    {
        $vessel = $this->vessel();
        $date = now()->addDays(5)->toDateString();

        FerrySchedule::create([
            'ferry_route_id' => $this->route(25)->id,
            'vessel_id' => $vessel->id,
            'departure_date' => $date,
            'departure_time' => '09:00:00',
        ]);

        $this->actingAs($this->operator())
            ->post(route('ferry.staff.schedules.store'), [
                'ferry_route_id' => $this->route(25)->id,
                'vessel_id' => $vessel->id,
                'departure_date' => $date,
                'departure_time' => '10:00',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('ferry_schedules', 2);
    }

    public function test_an_empty_sailing_can_be_cancelled(): void
    {
        $schedule = FerrySchedule::create([
            'ferry_route_id' => $this->route()->id,
            'vessel_id' => $this->vessel()->id,
            'departure_date' => now()->addDays(5)->toDateString(),
            'departure_time' => '09:30:00',
        ]);

        $this->actingAs($this->operator())
            ->post(route('ferry.staff.schedules.cancel', $schedule))
            ->assertRedirect();

        $this->assertSame('cancelled', $schedule->fresh()->status);
    }

    /**
     * UC-13 E1.
     */
    public function test_a_sailing_with_passengers_cannot_be_cancelled(): void
    {
        $schedule = FerrySchedule::create([
            'ferry_route_id' => $this->route()->id,
            'vessel_id' => $this->vessel()->id,
            'departure_date' => now()->addDays(3)->toDateString(),
            'departure_time' => '09:30:00',
        ]);

        $visitor = User::factory()->role('visitor')->create();
        $hotel = Hotel::firstOrCreate(['name' => 'Palm Reef Hotel'], ['star_rating' => 4]);

        $booking = HotelBooking::create([
            'user_id' => $visitor->id,
            'hotel_id' => $hotel->id,
            'reference' => 'PIB-HB-TEST99',
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(10)->toDateString(),
            'guests' => 1,
            'total_amount' => 100.00,
            'status' => 'confirmed',
        ]);

        app(FerryTicketIssueService::class)->issue($schedule, $booking->id, $visitor, null, 'card');

        $this->actingAs($this->operator())
            ->post(route('ferry.staff.schedules.cancel', $schedule))
            ->assertSessionHas('error');

        $this->assertSame('scheduled', $schedule->fresh()->status);
    }

    /**
     * Reassigning to a smaller boat must not strand passengers who already hold a pass.
     */
    public function test_a_sailing_cannot_be_moved_to_a_vessel_too_small_for_its_passengers(): void
    {
        $route = $this->route();

        $schedule = FerrySchedule::create([
            'ferry_route_id' => $route->id,
            'vessel_id' => $this->vessel('active', 100)->id,
            'departure_date' => now()->addDays(5)->toDateString(),
            'departure_time' => '09:30:00',
            'seats_taken' => 40,
        ]);

        $this->actingAs($this->operator())
            ->put(route('ferry.staff.schedules.update', $schedule), [
                'ferry_route_id' => $route->id,
                'vessel_id' => $this->vessel('active', 20)->id,
                'departure_date' => $schedule->departure_date->toDateString(),
                'departure_time' => '09:30',
                'status' => 'scheduled',
            ])
            ->assertSessionHas('error');

        $this->assertSame(40, $schedule->fresh()->seats_taken);
    }

    public function test_only_a_ferry_operator_can_schedule(): void
    {
        foreach (['visitor', 'hotel_staff', 'park_staff', 'admin'] as $role) {
            $this->actingAs(User::factory()->role($role)->create())
                ->get(route('ferry.staff.schedules.create'))
                ->assertForbidden();
        }
    }
}
