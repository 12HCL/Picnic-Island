<?php

/*
|--------------------------------------------------------------------------
| Module 3 - Ferry
|--------------------------------------------------------------------------
| Owner: Ali Naayif. This file is yours alone - nobody else edits it, and you
| edit no one else's. That is the whole point of the split (BUILD_CONTRACT.md §2):
| five people in one routes/web.php is the commonest merge conflict on a
| five-person Laravel project.
|
| It is loaded by routes/web.php, so anything you add here is live immediately.
|
| Conventions (BUILD_CONTRACT.md §2) - derive names, do not invent them:
|   URL          kebab-case, plural       /ferry/schedules
|   Route name   dot-separated, matches   ferry.schedules.index
|   Controller   App\Http\Controllers\Ferry\...
|   View         resources/views/ferry/<resource>/<action>.blade.php
|
| Role middleware is Faain's and applies to every module:
|   Route::middleware(['auth', 'role:ferry_operator'])->group(function () { ... });
| The five role names are visitor, hotel_staff, ferry_operator, park_staff, admin,
| exactly as seeded in `roles`.`name`. Nobody invents a sixth.
|
| Your routes are listed in BUILD_CONTRACT.md §3 - that table is the contract.
*/

use App\Http\Controllers\Ferry\FerryScheduleController;
use App\Http\Controllers\Ferry\FerryTicketController;
use App\Http\Controllers\Ferry\ManifestController;
use App\Http\Controllers\Ferry\StaffDashboardController;
use App\Http\Controllers\Ferry\StaffScheduleController;
use App\Http\Controllers\Ferry\StaffTicketController;
use App\Http\Controllers\Ferry\ValidationController;
use Illuminate\Support\Facades\Route;

// ── Public (no auth) ─────────────────────────────────────────────────────────
// Browsing runs before booking: a visitor sees what is sailing before being asked
// to log in. BR-01 is checked on the booking page, after authentication.
Route::get('/ferry/schedules', [FerryScheduleController::class, 'index'])
    ->name('ferry.schedules.index');

/*
| Visitor booking journey. BR-01 is enforced inside the controller, not by withholding
| the route: a visitor with no hotel booking may reach this page and is refused on it.
|
| role:visitor answers a different question - who this page is for. Staff issue tickets
| through counter issuance (UC-14), which is its own controller, so they lose nothing by
| being kept off the visitor-facing page. Added 18 September after QA finding #3, which
| recorded every authenticated role reaching this route.
*/
Route::middleware(['auth', 'role:visitor'])->group(function () {
    Route::get('/ferry/schedules/{schedule}/book', [FerryTicketController::class, 'create'])
        ->name('ferry.tickets.create');
    // Payment is a simulated confirmation, so the booking form is also the pay screen and
    // this is its submit. MASTER_SCHEMA.md §11 writes the ferry_tickets row only here, at
    // confirmation, so there is no ticket to hang a /ferry/tickets/{ticket}/pay route off —
    // the same conclusion module 4 reached for park tickets.
    Route::post('/ferry/tickets', [FerryTicketController::class, 'store'])
        ->name('ferry.tickets.store');
});

// ── Visitor OR ferry_operator — one ticket ───────────────────────────────────
// The owner sees their own pass; an operator sees any. Role middleware cannot express
// "the owner or this one staff role", so the check is in the controller — written as
// "who is allowed", after QA finding #1 showed "who is forbidden" lets roles fall through.
Route::middleware('auth')->group(function () {
    Route::get('/ferry/tickets/{ticket}', [FerryTicketController::class, 'show'])
        ->name('ferry.tickets.show');
});

// ── Ferry operator ───────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:ferry_operator'])->group(function () {

    // Staff landing page — named ferry.dashboard to match DashboardController's per-role
    // redirect map, the same way hotel's and park's are.
    Route::get('/staff/ferry', [StaffDashboardController::class, 'index'])
        ->name('ferry.dashboard');

    // Counter issuance — UC-14. Same service, same rules as the visitor's own purchase.
    Route::get('/staff/ferry/issue', [StaffTicketController::class, 'create'])
        ->name('ferry.staff.issue');
    Route::post('/staff/ferry/issue', [StaffTicketController::class, 'store'])
        ->name('ferry.staff.issue.store');

    // Boarding validation at the jetty.
    Route::get('/staff/ferry/validate', [ValidationController::class, 'index'])
        ->name('ferry.staff.validate');
    Route::post('/staff/ferry/validate', [ValidationController::class, 'check'])
        ->name('ferry.staff.validate.check');
    // Recording boarding is a separate, deliberate POST: a lookup must never mutate a pass,
    // or an operator's mistyped reference becomes impossible to undo.
    Route::post('/staff/ferry/validate/{ticket}/board', [ValidationController::class, 'board'])
        ->name('ferry.staff.validate.board');

    Route::get('/staff/ferry/schedules', [StaffScheduleController::class, 'index'])
        ->name('ferry.staff.schedules.index');

    Route::get('/staff/ferry/manifest/{schedule}', [ManifestController::class, 'show'])
        ->name('ferry.staff.manifest');
});
