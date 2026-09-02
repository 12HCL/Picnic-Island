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

use Illuminate\Support\Facades\Route;

// Ahmed Malaaz Mohamed: your routes go here.
