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
 * Module 3 — boarding validation at the jetty.
 *
 * The point of these: the boarding button is hidden on an invalid pass, but hiding it is a
 * courtesy, not the rule. The server re-runs the same check, for the same reason BR-01 is
 * enforced in the controller rather than by withholding the booking form.
 *
 * Found by review on 18 September: board() previously checked only that the ticket was
 * 'issued', so posting the action directly boarded a pass for a sailing days away.
 */
class FerryValidationTest extends TestCase
{
    use RefreshDatabase;

    private function ticketFor(string $departureDate, string $scheduleStatus = 'scheduled')
    {
        $route = FerryRoute::create([
            'origin' => 'Mainland Jetty',
            'destination' => 'North Jetty',
            'duration_minutes' => 25,
            'base_fare' => 45.00,
        ]);

        $vessel = Vessel::create(['name' => 'MV Coral Queen', 'capacity' => 120, 'status' => 'active']);

        $schedule = FerrySchedule::create([
            'ferry_route_id' => $route->id,
            'vessel_id' => $vessel->id,
            'departure_date' => $departureDate,
            'departure_time' => '09:30:00',
            'status' => 'scheduled',
        ]);

        $visitor = User::factory()->role('visitor')->create();
        $hotel = Hotel::firstOrCreate(['name' => 'Palm Reef Hotel'], ['star_rating' => 4]);

        $booking = HotelBooking::create([
            'user_id' => $visitor->id,
            'hotel_id' => $hotel->id,
            'reference' => 'PIB-HB-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'check_in' => now()->subDay()->toDateString(),
            'check_out' => now()->addDays(10)->toDateString(),
            'guests' => 2,
            'total_amount' => 780.00,
            'status' => 'confirmed',
        ]);

        $ticket = app(FerryTicketIssueService::class)
            ->issue($schedule, $booking->id, $visitor, null, 'card');

        // Set after issuing: the service refuses to sell for a cancelled sailing, but an
        // already-issued pass can be left holding one when operations cancel the crossing.
        if ($scheduleStatus !== 'scheduled') {
            $schedule->update(['status' => $scheduleStatus]);
        }

        return $ticket;
    }

    private function operator(): User
    {
        return User::factory()->role('ferry_operator')->create();
    }

    public function test_a_pass_for_today_may_be_boarded(): void
    {
        $ticket = $this->ticketFor(now()->toDateString());

        $this->actingAs($this->operator())
            ->post(route('ferry.staff.validate.board', $ticket))
            ->assertRedirect();

        $this->assertSame('boarded', $ticket->fresh()->status);
    }

    /**
     * The regression this suite exists for.
     */
    public function test_a_pass_for_another_day_cannot_be_boarded_by_posting_directly(): void
    {
        $ticket = $this->ticketFor(now()->addDays(3)->toDateString());

        $this->actingAs($this->operator())
            ->post(route('ferry.staff.validate.board', $ticket))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('issued', $ticket->fresh()->status);
    }

    public function test_a_pass_on_a_cancelled_sailing_cannot_be_boarded(): void
    {
        $ticket = $this->ticketFor(now()->toDateString(), 'cancelled');

        $this->actingAs($this->operator())
            ->post(route('ferry.staff.validate.board', $ticket))
            ->assertSessionHas('error');

        $this->assertSame('issued', $ticket->fresh()->status);
    }

    public function test_a_pass_cannot_be_boarded_twice(): void
    {
        $ticket = $this->ticketFor(now()->toDateString());
        $operator = $this->operator();

        $this->actingAs($operator)->post(route('ferry.staff.validate.board', $ticket));
        $this->assertSame('boarded', $ticket->fresh()->status);

        $this->actingAs($operator)
            ->post(route('ferry.staff.validate.board', $ticket))
            ->assertSessionHas('error');
    }

    public function test_the_lookup_reports_why_a_pass_is_not_valid(): void
    {
        $ticket = $this->ticketFor(now()->addDays(3)->toDateString());

        $this->actingAs($this->operator())
            ->post(route('ferry.staff.validate.check'), ['reference' => $ticket->reference])
            ->assertOk()
            ->assertSee('Not valid for boarding')
            ->assertSee('not today');
    }

    public function test_an_unknown_reference_is_reported_rather_than_erroring(): void
    {
        $this->actingAs($this->operator())
            ->post(route('ferry.staff.validate.check'), ['reference' => 'PIB-FT-999999'])
            ->assertOk()
            ->assertSee('No ferry ticket found');
    }

    /**
     * A lookup must never mutate the pass, or a mistyped reference becomes impossible to
     * undo.
     */
    public function test_looking_a_pass_up_does_not_change_it(): void
    {
        $ticket = $this->ticketFor(now()->toDateString());

        $this->actingAs($this->operator())
            ->post(route('ferry.staff.validate.check'), ['reference' => $ticket->reference]);

        $this->assertSame('issued', $ticket->fresh()->status);
    }

    public function test_only_a_ferry_operator_can_board_a_pass(): void
    {
        $ticket = $this->ticketFor(now()->toDateString());

        foreach (['visitor', 'hotel_staff', 'park_staff', 'admin'] as $role) {
            $this->actingAs(User::factory()->role($role)->create())
                ->post(route('ferry.staff.validate.board', $ticket))
                ->assertForbidden();
        }

        $this->assertSame('issued', $ticket->fresh()->status);
    }

    // ── Filter input on the timetables ───────────────────────────────────────

    public function test_a_malformed_date_filter_is_rejected_rather_than_crashing(): void
    {
        $this->get('/ferry/schedules?date=abc')->assertSessionHasErrors('date');

        $this->actingAs($this->operator())
            ->get('/staff/ferry/schedules?date=abc')
            ->assertSessionHasErrors('date');
    }

    public function test_a_valid_date_filter_still_works(): void
    {
        $this->ticketFor(now()->addDays(3)->toDateString());

        $this->get('/ferry/schedules?date='.now()->addDays(3)->toDateString())->assertOk();
        $this->get('/ferry/schedules')->assertOk();
    }
}
