<?php

namespace Tests\Feature\Park;

use App\Models\ParkActivity;
use App\Models\ParkEvent;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Module 4 — the activity catalogue and the public events listing.
 * BUILD_CONTRACT.md §3. MASTER_SCHEMA.md §12–§13.
 */
class ParkActivityTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $visitor;
    private ParkActivity $activity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->staff = User::factory()->role('park_staff')->create();
        $this->visitor = User::factory()->role('visitor')->create();

        $this->activity = ParkActivity::create([
            'name' => 'Sunset Coaster',
            'type' => 'ride',
            'description' => 'The big one on the east ridge.',
            'default_capacity' => 40,
            'base_price' => 25.00,
            'is_active' => true,
        ]);
    }

    // ── Access control ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_to_login_from_the_catalogue(): void
    {
        $this->get(route('park.staff.activities.index'))->assertRedirect(route('login'));
    }

    public function test_visitor_cannot_reach_the_catalogue(): void
    {
        $this->actingAs($this->visitor)
            ->get(route('park.staff.activities.index'))
            ->assertForbidden();
    }

    public function test_park_staff_can_see_the_catalogue(): void
    {
        $this->actingAs($this->staff)
            ->get(route('park.staff.activities.index'))
            ->assertOk()
            ->assertSee('Sunset Coaster');
    }

    // ── CRUD ──────────────────────────────────────────────────────────────────

    public function test_the_create_form_renders(): void
    {
        $this->actingAs($this->staff)
            ->get(route('park.staff.activities.create'))
            ->assertOk()
            ->assertSee('Add to catalogue');
    }

    public function test_the_edit_form_renders_with_the_current_values(): void
    {
        $this->actingAs($this->staff)
            ->get(route('park.staff.activities.edit', $this->activity))
            ->assertOk()
            ->assertSee('Sunset Coaster');
    }

    public function test_the_activity_page_lists_its_scheduled_events(): void
    {
        $this->event(3);

        $this->actingAs($this->staff)
            ->get(route('park.staff.activities.show', $this->activity))
            ->assertOk()
            ->assertSee('Sunset Coaster')
            ->assertSee('17:30');
    }

    public function test_park_staff_can_add_an_activity(): void
    {
        $response = $this->actingAs($this->staff)->post(route('park.staff.activities.store'), [
            'name' => 'Dolphin Show',
            'type' => 'show',
            'description' => 'Twice daily at the lagoon.',
            'default_capacity' => 120,
            'base_price' => 15.50,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('park.staff.activities.index'));
        $this->assertDatabaseHas('park_activities', [
            'name' => 'Dolphin Show',
            'type' => 'show',
            'default_capacity' => 120,
        ]);
    }

    /**
     * Beach events are a type on this table rather than a table of their own
     * (MASTER_SCHEMA.md §12), so the catalogue must accept one.
     */
    public function test_a_beach_event_is_stored_as_a_type(): void
    {
        $this->actingAs($this->staff)->post(route('park.staff.activities.store'), [
            'name' => 'Beach BBQ',
            'type' => 'beach_event',
            'default_capacity' => 60,
            'base_price' => 45.00,
        ]);

        $this->assertDatabaseHas('park_activities', [
            'name' => 'Beach BBQ',
            'type' => 'beach_event',
        ]);
    }

    public function test_an_invented_type_is_rejected(): void
    {
        $this->actingAs($this->staff)
            ->post(route('park.staff.activities.store'), [
                'name' => 'Submarine Ride',
                'type' => 'submarine',
                'default_capacity' => 10,
                'base_price' => 80.00,
            ])
            ->assertSessionHasErrors('type');

        $this->assertDatabaseMissing('park_activities', ['name' => 'Submarine Ride']);
    }

    public function test_an_activity_with_no_capacity_is_rejected(): void
    {
        $this->actingAs($this->staff)
            ->post(route('park.staff.activities.store'), [
                'name' => 'Ghost Ride',
                'type' => 'ride',
                'default_capacity' => 0,
                'base_price' => 5.00,
            ])
            ->assertSessionHasErrors('default_capacity');
    }

    /**
     * An unchecked checkbox posts nothing at all. Without prepareForValidation that would
     * leave is_active null against a NOT NULL column.
     */
    public function test_omitting_the_active_checkbox_stores_false_rather_than_failing(): void
    {
        $this->actingAs($this->staff)->post(route('park.staff.activities.store'), [
            'name' => 'Retired Carousel',
            'type' => 'ride',
            'default_capacity' => 20,
            'base_price' => 10.00,
        ]);

        $this->assertDatabaseHas('park_activities', [
            'name' => 'Retired Carousel',
            'is_active' => false,
        ]);
    }

    public function test_park_staff_can_update_an_activity(): void
    {
        $this->actingAs($this->staff)->put(route('park.staff.activities.update', $this->activity), [
            'name' => 'Sunset Coaster II',
            'type' => 'ride',
            'default_capacity' => 50,
            'base_price' => 30.00,
            'is_active' => '1',
        ])->assertRedirect(route('park.staff.activities.index'));

        $this->assertDatabaseHas('park_activities', [
            'id' => $this->activity->id,
            'name' => 'Sunset Coaster II',
            'default_capacity' => 50,
        ]);
    }

    public function test_visitor_cannot_add_an_activity(): void
    {
        $this->actingAs($this->visitor)
            ->post(route('park.staff.activities.store'), [
                'name' => 'Free Rides For Me',
                'type' => 'ride',
                'default_capacity' => 1,
                'base_price' => 0,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('park_activities', ['name' => 'Free Rides For Me']);
    }

    // ── Public listing ────────────────────────────────────────────────────────

    public function test_the_events_listing_is_public(): void
    {
        $this->event(3);

        $this->get(route('park.events.index'))
            ->assertOk()
            ->assertSee('Sunset Coaster');
    }

    public function test_a_past_event_is_not_listed(): void
    {
        $this->event(-2);

        $this->get(route('park.events.index'))->assertDontSee('Sunset Coaster');
    }

    public function test_a_sold_out_event_is_not_listed(): void
    {
        $event = $this->event(3);
        $event->update(['seats_taken' => $event->capacity]);

        $this->get(route('park.events.index'))->assertDontSee('Sunset Coaster');
    }

    public function test_an_event_of_a_deactivated_activity_is_not_listed(): void
    {
        $this->event(3);
        $this->activity->update(['is_active' => false]);

        $this->get(route('park.events.index'))->assertDontSee('Sunset Coaster');
    }

    public function test_the_listing_can_be_filtered_by_type(): void
    {
        $this->event(3);

        $this->get(route('park.events.index', ['type' => 'ride']))->assertSee('Sunset Coaster');
        $this->get(route('park.events.index', ['type' => 'beach_event']))->assertDontSee('Sunset Coaster');
    }

    public function test_the_event_page_shows_what_is_left(): void
    {
        $event = $this->event(3);
        $event->update(['seats_taken' => 37]);

        $this->get(route('park.events.show', $event))
            ->assertOk()
            ->assertSee('Sunset Coaster')
            ->assertSee('3');
    }

    /**
     * Schedule one instance of the activity, offset in days from today.
     */
    private function event(int $daysFromNow): ParkEvent
    {
        return ParkEvent::create([
            'park_activity_id' => $this->activity->id,
            'event_date' => now()->addDays($daysFromNow)->toDateString(),
            'start_time' => '17:30',
            'capacity' => 40,
            'price' => 25.00,
            'status' => 'scheduled',
        ]);
    }
}
