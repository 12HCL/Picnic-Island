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

use App\Http\Controllers\Ferry\FerryTicketController;
use Illuminate\Support\Facades\Route;

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
});

/*
| Still to add — BUILD_CONTRACT.md §3:
|   GET  /ferry/schedules                      FerryScheduleController@index   public
|   POST /ferry/tickets                        FerryTicketController@store     visitor
|   GET  /ferry/tickets/{ticket}               FerryTicketController@show      visitor, operator
|   GET  /staff/ferry ... ->middleware(['auth', 'role:ferry_operator'])
*/
