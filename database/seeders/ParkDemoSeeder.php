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
 * Run on its own, so it does not touch the shared DatabaseSeeder:
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

        $tomorrowCoaster = $this->event($coaster, now()->addDay()->toDateString(), '17:30:00', 40, 25.00);
        $this->event($dolphins, now()->addDay()->toDateString(), '11:00:00', 120, 15.00);
        $this->event($coaster, now()->addDays(3)->toDateString(), '17:30:00', 40, 25.00);

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
    ): ParkEvent {
        return ParkEvent::firstOrCreate(
            [
                'park_activity_id' => $activity->id,
                'event_date' => $date,
                'start_time' => $time,
            ],
            [
                'capacity' => $capacity,
                'price' => $price,
                'status' => 'scheduled',
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
