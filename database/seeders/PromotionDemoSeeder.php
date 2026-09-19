<?php

namespace Database\Seeders;

use App\Models\Promotion;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Module 5 — Content. Demonstration promotions.
 *
 * Nothing seeded the `promotions` table, so a fresh clone showed an empty "Current offers"
 * section on the front page and an empty list at /admin/promotions — the screens work, but
 * there was nothing in them to see.
 *
 * One promotion per module plus a general one, so the module filter has something to filter
 * and the home page section is full. Each is attributed to the staff member who would really
 * write it: `created_by` is NOT NULL, and hotel and park staff manage their own module's
 * offers while the administrator manages all of them.
 *
 * Called by DatabaseSeeder on migrate --seed. It can also be run on its own:
 *     php artisan db:seed --class=PromotionDemoSeeder
 *
 * Every row is keyed with updateOrCreate, so running it twice updates rather than duplicates.
 */
class PromotionDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = $this->userWithRole('admin');
        $hotelStaff = $this->userWithRole('hotel_staff') ?? $admin;
        $parkStaff = $this->userWithRole('park_staff') ?? $admin;

        if (! $admin) {
            $this->command?->warn('No admin user — run RoleSeeder and AdminDemoSeeder first.');

            return;
        }

        $offers = [
            [
                'title' => 'Three nights, fourth free',
                'body' => 'Book three consecutive nights at Palm Reef Hotel and the fourth is on us. Sea-view rooms included.',
                'module' => 'hotel',
                'created_by' => $hotelStaff->id,
            ],
            [
                'title' => 'Family day pass — two children free',
                'body' => 'Two children go free with every two adult day passes to the theme park. Rides, shows and the beach are all included.',
                'module' => 'park',
                'created_by' => $parkStaff->id,
            ],
            [
                'title' => 'Island explorer week',
                'body' => 'Stay, cross and play: hotel guests get priority boarding on every sailing and ten per cent off park events all week.',
                'module' => 'general',
                'created_by' => $admin->id,
            ],
        ];

        foreach ($offers as $offer) {
            Promotion::updateOrCreate(
                ['title' => $offer['title']],
                $offer + [
                    'starts_on' => now()->subDays(2)->toDateString(),
                    'ends_on' => now()->addDays(30)->toDateString(),
                    'is_published' => true,
                ],
            );
        }

        // An expired one, so the `live()` scope is visibly doing something: it is listed at
        // /admin/promotions but must not appear on the front page.
        Promotion::updateOrCreate(
            ['title' => 'Monsoon weekend escape'],
            [
                'body' => 'Half-price weekend stays through the monsoon season. This offer has ended.',
                'module' => 'hotel',
                'created_by' => $hotelStaff->id,
                'starts_on' => now()->subDays(40)->toDateString(),
                'ends_on' => now()->subDays(5)->toDateString(),
                'is_published' => true,
            ],
        );

        $this->command?->info('Promotion demo data ready — 4 offers, 3 of them live.');
    }

    private function userWithRole(string $role): ?User
    {
        $roleId = Role::where('name', $role)->value('id');

        return $roleId ? User::where('role_id', $roleId)->first() : null;
    }
}
