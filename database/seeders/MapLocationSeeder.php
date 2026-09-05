<?php

namespace Database\Seeders;

use App\Models\MapLocation;
use Illuminate\Database\Seeder;

/**
 * Module 5 — Content, Map & Reporting. Owner: Ahmed Safhaan.
 *
 * Demonstration data for the island map. The pos_x / pos_y percentages match the
 * landmarks drawn on public/img/island-map.svg, so each marker lands on the thing
 * it names.
 *
 * Run on its own, so it does not touch the shared DatabaseSeeder:
 *     php artisan db:seed --class=MapLocationSeeder
 *
 * updateOrCreate keyed on `name` makes it safe to run twice — it updates the
 * existing row rather than creating a duplicate.
 */
class MapLocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            [
                'name' => 'North Jetty',
                'category' => 'jetty',
                'description' => 'Main arrival point for the ferry from the mainland. Ticket counter and waiting shelter on the pier.',
                'pos_x' => 50.00,
                'pos_y' => 12.00,
            ],
            [
                'name' => 'Palm Reef Hotel',
                'category' => 'hotel',
                'description' => 'The island hotel, with standard rooms, deluxe rooms and beach villas. Check-in from 14:00.',
                'pos_x' => 33.00,
                'pos_y' => 38.00,
            ],
            [
                'name' => 'Visitor Centre',
                'category' => 'facility',
                'description' => 'Information desk, first aid, lockers and the lost property office. Staffed daily 08:00 to 20:00.',
                'pos_x' => 48.00,
                'pos_y' => 50.00,
            ],
            [
                'name' => 'Sunset Coaster',
                'category' => 'attraction',
                'description' => 'The theme park headline ride, on the eastern ridge. Height restrictions apply.',
                'pos_x' => 62.00,
                'pos_y' => 34.00,
            ],
            [
                'name' => 'Dolphin Cove',
                'category' => 'attraction',
                'description' => 'Sheltered lagoon used for the dolphin show and guided snorkelling sessions.',
                'pos_x' => 78.00,
                'pos_y' => 58.00,
            ],
            [
                'name' => 'Coral Beach',
                'category' => 'beach',
                'description' => 'West-facing swimming beach with sun loungers and umbrellas. Beach events and evening barbecues are held here.',
                'pos_x' => 17.00,
                'pos_y' => 62.00,
            ],
            [
                'name' => 'South Jetty',
                'category' => 'jetty',
                'description' => 'Smaller pier used for excursion boats and the return crossing at the end of the day.',
                'pos_x' => 55.00,
                'pos_y' => 88.00,
            ],
        ];

        foreach ($locations as $location) {
            MapLocation::updateOrCreate(
                ['name' => $location['name']],
                $location + ['is_visible' => true],
            );
        }
    }
}
