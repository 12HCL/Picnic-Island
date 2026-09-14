<?php

/*
|--------------------------------------------------------------------------
| Module 1 - Auth, Roles & Admin
|--------------------------------------------------------------------------
| Owner: Mohamed Faain. This file is yours alone - nobody else edits it, and you
| edit no one else's. That is the whole point of the split (BUILD_CONTRACT.md §2):
| five people in one routes/web.php is the commonest merge conflict on a
| five-person Laravel project.
|
| It is loaded by routes/web.php, so anything you add here is live immediately.
|
| Conventions (BUILD_CONTRACT.md §2) - derive names, do not invent them:
|   URL          kebab-case, plural       /admin/users
|   Route name   dot-separated, matches   admin.users.index
|   Controller   App\Http\Controllers\Admin\...
|   View         resources/views/admin/<resource>/<action>.blade.php
|
| Role middleware is Faain's and applies to every module:
|   Route::middleware(['auth', 'role:admin'])->group(function () { ... });
| The five role names are visitor, hotel_staff, ferry_operator, park_staff, admin,
| exactly as seeded in `roles`.`name`. Nobody invents a sixth.
|
| Your routes are listed in BUILD_CONTRACT.md §3 - that table is the contract.
*/

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Visitor\MyBookingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/users', [UserController::class, 'index'])
        ->name('admin.users.index');
    Route::get('/admin/users/create', [UserController::class, 'create'])
        ->name('admin.users.create');
    Route::post('/admin/users', [UserController::class, 'store'])
        ->name('admin.users.store');
    Route::get('/admin/users/{user}', [UserController::class, 'show'])
        ->name('admin.users.show');
    Route::get('/admin/users/{user}/edit', [UserController::class, 'edit'])
        ->name('admin.users.edit');
    Route::put('/admin/users/{user}', [UserController::class, 'update'])
        ->name('admin.users.update');
    Route::post('/admin/users/{user}/deactivate', [UserController::class, 'deactivate'])
        ->name('admin.users.deactivate');
    Route::post('/admin/users/{user}/activate', [UserController::class, 'activate'])
        ->name('admin.users.activate');
});

Route::middleware(['auth', 'role:visitor'])->group(function () {
    Route::get('/my-bookings', [MyBookingsController::class, 'index'])
        ->name('visitor.bookings.index');
});
