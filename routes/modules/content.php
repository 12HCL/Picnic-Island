<?php

/*
|--------------------------------------------------------------------------
| Module 5 - Content, Map & Reporting
|--------------------------------------------------------------------------
| Owner: Ahmed Safhaan. This file is yours alone - nobody else edits it, and you
| edit no one else's. That is the whole point of the split (BUILD_CONTRACT.md §2):
| five people in one routes/web.php is the commonest merge conflict on a
| five-person Laravel project.
|
| It is loaded by routes/web.php, so anything you add here is live immediately.
|
| Conventions (BUILD_CONTRACT.md §2) - derive names, do not invent them:
|   URL          kebab-case, plural       /admin/map-locations
|   Route name   dot-separated, matches   content.map-locations.index
|   Controller   App\Http\Controllers\Content\...
|   View         resources/views/content/<resource>/<action>.blade.php
|
| Role middleware is Faain's and applies to every module:
|   Route::middleware(['auth', 'role:admin'])->group(function () { ... });
| The five role names are visitor, hotel_staff, ferry_operator, park_staff, admin,
| exactly as seeded in `roles`.`name`. Nobody invents a sixth.
|
| Your routes are listed in BUILD_CONTRACT.md §3 - that table is the contract.
*/

use App\Http\Controllers\Content\MapController;
use App\Http\Controllers\Content\MapLocationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Content\PromotionController;

// ── Public (no auth) ─────────────────────────────────────────────────────────
// UC-08. No login required: the map is what a prospective visitor looks at
// before they have an account.
Route::get('/map', [MapController::class, 'index'])->name('content.map');

// ── Admin — island map management ────────────────────────────────────────────
// UC-18 steps 1-4. Login landed on 5 September, so 'auth' now has a real login
// route to send a guest to and these screens are properly protected.
//
// The seven CRUD actions are written out rather than using Route::resource,
// because a resource declared on a slashed URI ('admin/map-locations') derives a
// malformed parameter name and route-model binding then fails. Explicit routes
// also match how routes/modules/park.php is written.
//
// {mapLocation} matches the MapLocation $mapLocation argument in the controller,
// so Laravel looks the row up by id and hands over the model - or 404s by itself
// if the id does not exist. That is route-model binding.
Route::middleware(['auth', 'role:admin'])->group(function () {

    // READ - list
    Route::get('/admin/map-locations', [MapLocationController::class, 'index'])
        ->name('content.map-locations.index');

    // CREATE - form, then save. 'create' is declared before '{mapLocation}' or the
    // word "create" would be captured as an id by the show route below.
    Route::get('/admin/map-locations/create', [MapLocationController::class, 'create'])
        ->name('content.map-locations.create');
    Route::post('/admin/map-locations', [MapLocationController::class, 'store'])
        ->name('content.map-locations.store');

    // READ - one
    Route::get('/admin/map-locations/{mapLocation}', [MapLocationController::class, 'show'])
        ->name('content.map-locations.show');

    // UPDATE - form, then save
    Route::get('/admin/map-locations/{mapLocation}/edit', [MapLocationController::class, 'edit'])
        ->name('content.map-locations.edit');
    Route::put('/admin/map-locations/{mapLocation}', [MapLocationController::class, 'update'])
        ->name('content.map-locations.update');

    // DELETE
    Route::delete('/admin/map-locations/{mapLocation}', [MapLocationController::class, 'destroy'])
        ->name('content.map-locations.destroy');

});

// admin, hotel staff and park staff - promotions
// hotel and park staff manage promotions - below is ofr that
// admin can manage all of them

// The six remaining actions written out the same way as map-locations above, and for the
// same reason: 'create' is declared before '{promotion}', or the word "create" is captured
// as an id by the show route. PromotionController already implemented all seven; only
// index was registered, so every other method was unreachable.
Route::middleware(['auth', 'role:admin,hotel_staff,park_staff'])->group(function () {

    // READ - list
    Route::get('/admin/promotions', [PromotionController::class, 'index'])
        ->name('content.promotions.index');

    // CREATE - form, then save
    Route::get('/admin/promotions/create', [PromotionController::class, 'create'])
        ->name('content.promotions.create');
    Route::post('/admin/promotions', [PromotionController::class, 'store'])
        ->name('content.promotions.store');

    // READ - one
    Route::get('/admin/promotions/{promotion}', [PromotionController::class, 'show'])
        ->name('content.promotions.show');

    // UPDATE - form, then save
    Route::get('/admin/promotions/{promotion}/edit', [PromotionController::class, 'edit'])
        ->name('content.promotions.edit');
    Route::put('/admin/promotions/{promotion}', [PromotionController::class, 'update'])
        ->name('content.promotions.update');

    // DELETE
    Route::delete('/admin/promotions/{promotion}', [PromotionController::class, 'destroy'])
        ->name('content.promotions.destroy');

});




/*
| Still to build - BUILD_CONTRACT.md §3, Module 5:
|   GET /                      Content\HomeController@index      → view 'home'
|                              (and delete resources/views/scaffold-placeholder.blade.php
|                              plus the temporary route in routes/web.php when it lands)
|   /admin/promotions          Content\PromotionController       → full CRUD
|                              Roles: admin, hotel_staff, park_staff
|   Cross-module reporting     UC-18 steps 7-8
*/
