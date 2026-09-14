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

use App\Http\Controllers\Park\CapacityController;
use App\Http\Controllers\Park\GateSaleController;
use App\Http\Controllers\Park\ParkActivityController;
use App\Http\Controllers\Park\ParkEventController;
use App\Http\Controllers\Park\StaffDashboardController;
use App\Http\Controllers\Park\StaffEventController;
use App\Http\Controllers\Park\TicketController;
use App\Http\Controllers\Park\TicketValidationController;
use Illuminate\Support\Facades\Route;


// ── Public (no auth) ─────────────────────────────────────────────────────────
// Browsing runs before buying: the brief asks a visitor to see what is on before
// they are asked to log in.
Route::get('/park/events', [ParkEventController::class, 'index'])->name('park.events.index');
Route::get('/park/events/{event}', [ParkEventController::class, 'show'])->name('park.events.show');

// ── Visitor — online ticket purchase ─────────────────────────────────────────
Route::middleware(['auth', 'role:visitor'])->group(function () {
    // The simulated payment screen. MASTER_SCHEMA.md §14 writes the tickets row only at
    // confirmation, so nothing exists to hang a /park/tickets/{ticket}/pay route off yet —
    // see the note in the module 4 section of BUILD_CONTRACT.md that this raises.
    Route::get('/park/tickets/create', [TicketController::class, 'create'])
        ->name('park.tickets.create');
    Route::post('/park/tickets', [TicketController::class, 'store'])
        ->name('park.tickets.store');
});

// ── Visitor OR park_staff — a ticket (visitor sees own; staff sees any) ──────
Route::middleware(['auth'])->group(function () {
    Route::get('/park/tickets/{ticket}', [TicketController::class, 'show'])
        ->name('park.tickets.show');
    // Voiding is a status change through a named POST. There is no DELETE route for
    // tickets: a deleted row leaves the sales reports and its payment behind.
    Route::post('/park/tickets/{ticket}/cancel', [TicketController::class, 'cancel'])
        ->name('park.tickets.cancel');
});

// ── Park staff ───────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:park_staff'])->group(function () {

    // Staff landing page — named 'park.dashboard' to match DashboardController's
    // per-role redirect map, the same way hotel's is named 'hotel.dashboard'.
    Route::get('/staff/park', [StaffDashboardController::class, 'index'])
        ->name('park.dashboard');

    // At-entrance sales — the till.
    Route::get('/staff/park/sell', [GateSaleController::class, 'create'])
        ->name('park.staff.gate-sale');
    Route::post('/staff/park/sell', [GateSaleController::class, 'store'])
        ->name('park.staff.gate-sale.store');

    // On-site validation — UC-16.
    Route::get('/staff/park/validate', [TicketValidationController::class, 'index'])
        ->name('park.staff.validate');
    Route::post('/staff/park/validate', [TicketValidationController::class, 'validateTicket'])
        ->name('park.staff.validate.check');

    // Capacity monitoring — BR-06, reporting only.
    Route::get('/staff/park/capacity', [CapacityController::class, 'index'])
        ->name('park.staff.capacity');

    // Scheduling. Cancelling is a named POST, the same shape hotel uses for its status
    // transitions; DELETE stays for events nothing has been sold against.
    Route::post('/staff/park/events/{event}/cancel', [StaffEventController::class, 'cancel'])
        ->name('park.staff.events.cancel');
    Route::resource('staff/park/events', StaffEventController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
        ->names([
            'index'   => 'park.staff.events.index',
            'create'  => 'park.staff.events.create',
            'store'   => 'park.staff.events.store',
            'edit'    => 'park.staff.events.edit',
            'update'  => 'park.staff.events.update',
            'destroy' => 'park.staff.events.destroy',
        ])->parameters(['events' => 'event']);

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
