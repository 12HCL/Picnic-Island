<?php

namespace Database\Seeders;

use App\Models\ParkActivity;
use App\Models\ParkEvent;
use App\Models\Role;
use App\Models\User;
use App\Services\Park\TicketSalesService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Module 4 — Theme Park & Beach. Owner: Ahmed Malaaz Mohamed.
 *
 * Demonstration data for the module walkthrough and for the screenshots the report appendix
 * needs: a catalogue with all three activity types, events running today and later, and both
 * sales channels represented — including a sold-out event, because "this is full" is a state
 * the screens have to show.
 *
 * Called by DatabaseSeeder on migrate --seed. It can also be run on its own:
 *     php artisan db:seed --class=ParkDemoSeeder
 *
 * Every row is keyed with updateOrCreate or firstOrCreate, so running it twice updates
 * rather than duplicates. It creates its own demo accounts and does not modify any
 * existing user.
 *
 * Tickets are sold through TicketSalesService rather than inserted, so the demo data goes
 * through the same BR-06 transaction a real sale does and the seats_taken counters and
 * payments rows are consistent by construction rather than by hand.
 */
class ParkDemoSeeder extends Seeder
{
    /**
     * Demo accounts. Local only — these passwords are not used anywhere else.
     */
    private const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        $visitor = $this->demoUser('visitor', 'Aishath Visitor', 'park.visitor@picnic.test');
        $staff = $this->demoUser('park_staff', 'Ibrahim Park Staff', 'park.staff@picnic.test');

        // ── The catalogue: one of each type ──────────────────────────────────
        $coaster = ParkActivity::updateOrCreate(
            ['name' => 'Sunset Coaster'],
            [
                'type' => 'ride',
                'description' => 'The big one on the east ridge. Two inversions and a sea view.',
                'default_capacity' => 40,
                'base_price' => 25.00,
                'is_active' => true,
            ],
        );

        $dolphins = ParkActivity::updateOrCreate(
            ['name' => 'Dolphin Show'],
            [
                'type' => 'show',
                'description' => 'Forty minutes at the lagoon amphitheatre. Twice daily.',
                'default_capacity' => 120,
                'base_price' => 15.00,
                'is_active' => true,
            ],
        );

        // Beach events are a type here, not a separate table (MASTER_SCHEMA.md §12).
        $bbq = ParkActivity::updateOrCreate(
            ['name' => 'Beach BBQ & Bonfire'],
            [
                'type' => 'beach_event',
                'description' => 'Grilled reef fish on the west sandbank, from sunset.',
                'default_capacity' => 60,
                'base_price' => 45.00,
                'is_active' => true,
            ],
        );

        $lanterns = ParkActivity::updateOrCreate(
            ['name' => 'Lagoon Lantern Show'],
            [
                'type' => 'show',
                'description' => 'A sunset performance of music, light and stories beside the visitor centre lagoon.',
                'default_capacity' => 90,
                'base_price' => 18.00,
                'is_active' => true,
            ],
        );

        $drummers = ParkActivity::updateOrCreate(
            ['name' => 'Island Drummers'],
            [
                'type' => 'show',
                'description' => 'A high-energy Maldivian drumming performance on the open-air park stage.',
                'default_capacity' => 140,
                'base_price' => 12.00,
                'is_active' => true,
            ],
        );

        $reefQuest = ParkActivity::updateOrCreate(
            ['name' => 'Reef Quest'],
            [
                'type' => 'ride',
                'description' => 'An interactive family adventure through the island discovery trail.',
                'default_capacity' => 30,
                'base_price' => 18.00,
                'is_active' => true,
            ],
        );

        $nightMarket = ParkActivity::updateOrCreate(
            ['name' => 'Coral Night Market'],
            [
                'type' => 'beach_event',
                'description' => 'Local food stalls, crafts and live music along the west beach after sunset.',
                'default_capacity' => 100,
                'base_price' => 20.00,
                'is_active' => true,
            ],
        );

        // Retired, to show the catalogue's inactive state and prove visitors cannot see it.
        ParkActivity::updateOrCreate(
            ['name' => 'Old Carousel'],
            [
                'type' => 'ride',
                'description' => 'Withdrawn from service. Kept for the sales history.',
                'default_capacity' => 20,
                'base_price' => 8.00,
                'is_active' => false,
            ],
        );

        // ── The schedule ─────────────────────────────────────────────────────
        $today = now()->toDateString();

        $todayCoaster = $this->event($coaster, $today, '17:30:00', 40, 25.00);
        $todayDolphins = $this->event($dolphins, $today, '11:00:00', 120, 15.00);
        $todayBbq = $this->event($bbq, $today, '19:00:00', 60, 45.00);
        $todayLanterns = $this->event($lanterns, $today, '18:00:00', 90, 18.00);
        $todayDrummers = $this->event($drummers, $today, '16:00:00', 140, 12.00);
        $todayReefQuest = $this->event($reefQuest, $today, '13:30:00', 30, 18.00);
        $todayNightMarket = $this->event($nightMarket, $today, '20:00:00', 100, 20.00);

        $tomorrowCoaster = $this->event($coaster, now()->addDay()->toDateString(), '17:30:00', 40, 25.00);
        $tomorrowDate = now()->addDay()->toDateString();
        $this->event($dolphins, $tomorrowDate, '11:00:00', 120, 15.00);
        $this->event($lanterns, $tomorrowDate, '18:00:00', 90, 18.00);
        $this->event($drummers, $tomorrowDate, '16:00:00', 140, 12.00);
        $this->event($coaster, now()->addDays(3)->toDateString(), '17:30:00', 40, 25.00);
        $this->event($reefQuest, now()->addDays(3)->toDateString(), '14:00:00', 30, 18.00);
        $this->event($nightMarket, now()->addDays(5)->toDateString(), '20:00:00', 100, 20.00);

        // Historical states make the staff schedule and reporting screens demonstrable.
        $this->event($drummers, now()->subDay()->toDateString(), '16:00:00', 140, 12.00, 'completed');
        $this->event($lanterns, now()->addDays(2)->toDateString(), '18:00:00', 90, 18.00, 'cancelled');

        // A small event that will be filled completely, so the sold-out path is visible on
        // the listing, the till and the capacity dashboard.
        $soldOut = $this->event($dolphins, $today, '15:00:00', 6, 15.00);

        // ── Sales, through the real transaction ──────────────────────────────
        $sales = app(TicketSalesService::class);

        // Only seed sales once; re-running must not keep incrementing seats_taken.
        if ($todayCoaster->seats_taken === 0) {
            $sales->sell($todayCoaster, 2, 'online', $visitor, null, 'card');
            $sales->sell($todayCoaster->fresh(), 4, 'gate', null, $staff, 'cash');
        }

        if ($todayDolphins->seats_taken === 0) {
            $sales->sell($todayDolphins, 3, 'online', $visitor, null, 'transfer');
            $sales->sell($todayDolphins->fresh(), 8, 'gate', null, $staff, 'cash');
        }

        if ($todayBbq->seats_taken === 0) {
            $sales->sell($todayBbq, 2, 'online', $visitor, null, 'card');
        }

        if ($todayLanterns->seats_taken === 0) {
            $sales->sell($todayLanterns, 5, 'online', $visitor, null, 'card');
        }

        if ($todayDrummers->seats_taken === 0) {
            $sales->sell($todayDrummers, 10, 'gate', null, $staff, 'cash');
        }

        if ($todayReefQuest->seats_taken === 0) {
            $sales->sell($todayReefQuest, 3, 'online', $visitor, null, 'transfer');
        }

        if ($tomorrowCoaster->seats_taken === 0) {
            $sales->sell($tomorrowCoaster, 5, 'online', $visitor, null, 'card');
        }

        if ($soldOut->seats_taken === 0) {
            $sales->sell($soldOut, 6, 'gate', null, $staff, 'cash');
        }

        $this->command?->info('Park demo data seeded.');
        $this->command?->info('  visitor   park.visitor@picnic.test / ' . self::DEMO_PASSWORD);
        $this->command?->info('  park staff park.staff@picnic.test / ' . self::DEMO_PASSWORD);
    }

    /**
     * Keyed on the UNIQUE composite (activity, date, time), so re-running updates the row
     * that is already there rather than colliding with the index.
     */
    private function event(
        ParkActivity $activity,
        string $date,
        string $time,
        int $capacity,
        float $price,
        string $status = 'scheduled',
    ): ParkEvent {
        return ParkEvent::updateOrCreate(
            [
                'park_activity_id' => $activity->id,
                'event_date' => $date,
                'start_time' => $time,
            ],
            [
                'capacity' => $capacity,
                'price' => $price,
                'status' => $status,
            ],
        );
    }

    /**
     * role_id is not mass assignable on User, so it is set explicitly.
     */
    private function demoUser(string $role, string $name, string $email): User
    {
        $user = User::firstOrNew(['email' => $email]);

        $user->name = $name;
        $user->role_id = Role::where('name', $role)->value('id');
        $user->is_active = true;

        if (! $user->exists) {
            $user->password = Hash::make(self::DEMO_PASSWORD);
        }

        $user->save();

        return $user;
    }
}
