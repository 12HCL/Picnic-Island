<?php

namespace Tests\Feature\Hotel;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ?from=abc on the hotel booking report reached Carbon::parse() in the view and was a 500.
 */
class HotelReportDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unparseable_date_is_a_validation_error_not_a_crash(): void
    {
        $staff = User::factory()->role('hotel_staff')->create();

        $this->actingAs($staff)
            ->get(route('hotel.staff.reports.index', ['from' => 'abc', 'to' => 'xyz']))
            ->assertSessionHasErrors(['from', 'to']);
    }

    public function test_valid_dates_still_render_the_report(): void
    {
        $staff = User::factory()->role('hotel_staff')->create();

        $this->actingAs($staff)
            ->get(route('hotel.staff.reports.index', ['from' => '2026-09-01', 'to' => '2026-09-30']))
            ->assertOk();
    }
}
