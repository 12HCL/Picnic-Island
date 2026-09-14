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

use App\Http\Controllers\Park\GateSaleController;
use App\Http\Controllers\Park\ParkActivityController;
use App\Http\Controllers\Park\ParkEventController;
use App\Http\Controllers\Park\TicketController;
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

    // At-entrance sales — the till.
    Route::get('/staff/park/sell', [GateSaleController::class, 'create'])
        ->name('park.staff.gate-sale');
    Route::post('/staff/park/sell', [GateSaleController::class, 'store'])
        ->name('park.staff.gate-sale.store');

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
