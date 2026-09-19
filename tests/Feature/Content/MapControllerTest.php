<?php

namespace Tests\Feature\Content;

use App\Models\MapLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_public_map_displays_visible_locations_as_interactive_markers(): void
    {
        $visible = MapLocation::query()->create([
            'name' => 'Coral Beach',
            'category' => 'beach',
            'description' => 'A quiet beach on the west coast.',
            'pos_x' => 17,
            'pos_y' => 62,
            'is_visible' => true,
        ]);

        MapLocation::query()->create([
            'name' => 'Hidden Store Room',
            'category' => 'facility',
            'pos_x' => 50,
            'pos_y' => 50,
            'is_visible' => false,
        ]);

        $this->get(route('content.map'))
            ->assertOk()
            ->assertSee('Explore Picnic Island')
            ->assertSee('img/island-map-v2.webp', false)
            ->assertSee('data-interactive-map', false)
            ->assertSee('data-location-id="'.$visible->id.'"', false)
            ->assertSee('Coral Beach')
            ->assertDontSee('Hidden Store Room');
    }

    public function test_the_public_map_has_an_empty_state_when_no_locations_are_visible(): void
    {
        $this->get(route('content.map'))
            ->assertOk()
            ->assertSee('No locations have been added to the map yet.');
    }
}
