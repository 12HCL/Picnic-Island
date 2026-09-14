<?php

/*
|--------------------------------------------------------------------------
| Module 4 - Theme Park & Beach
|--------------------------------------------------------------------------
| Owner: Ahmed Malaaz Mohamed. This file is yours alone - nobody else edits it, and you
| edit no one else's. That is the whole point of the split (BUILD_CONTRACT.md §2):
| five people in one routes/web.php is the commonest merge conflict on a
| five-person Laravel project.
|
| It is loaded by routes/web.php, so anything you add here is live immediately.
|
| Conventions (BUILD_CONTRACT.md §2) - derive names, do not invent them:
|   URL          kebab-case, plural       /park/events
|   Route name   dot-separated, matches   park.events.index
|   Controller   App\Http\Controllers\Park\...
|   View         resources/views/park/<resource>/<action>.blade.php
|
| Role middleware is Faain's and applies to every module:
|   Route::middleware(['auth', 'role:park_staff'])->group(function () { ... });
| The five role names are visitor, hotel_staff, ferry_operator, park_staff, admin,
| exactly as seeded in `roles`.`name`. Nobody invents a sixth.
|
| Your routes are listed in BUILD_CONTRACT.md §3 - that table is the contract.
*/

use App\Http\Controllers\Park\ParkActivityController;
use App\Http\Controllers\Park\ParkEventController;
use Illuminate\Support\Facades\Route;


// ── Public (no auth) ─────────────────────────────────────────────────────────
// Browsing runs before buying: the brief asks a visitor to see what is on before
// they are asked to log in.
Route::get('/park/events', [ParkEventController::class, 'index'])->name('park.events.index');
Route::get('/park/events/{event}', [ParkEventController::class, 'show'])->name('park.events.show');

// ── Park staff ───────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:park_staff'])->group(function () {

    // Activity catalogue CRUD — full resource. destroy is allowed here, unlike tickets:
    // park_events.park_activity_id is restrictOnDelete, so MySQL refuses to remove an
    // activity that has ever been scheduled and the sales history cannot be orphaned.
    Route::resource('staff/park/activities', ParkActivityController::class)->names([
        'index'   => 'park.staff.activities.index',
        'create'  => 'park.staff.activities.create',
        'store'   => 'park.staff.activities.store',
        'show'    => 'park.staff.activities.show',
        'edit'    => 'park.staff.activities.edit',
        'update'  => 'park.staff.activities.update',
        'destroy' => 'park.staff.activities.destroy',
    ])->parameters(['activities' => 'activity']);
});
