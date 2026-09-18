<?php

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
| Shared file - owned by Faain as repo owner. Only two things belong here:
| the require lines below, and the authentication routes once BUILD_CONTRACT.md §0
| is decided.
|
| Module routes do NOT go in this file. Put them in your own routes/modules/<you>.php
| so five people are never editing the same file.
*/

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
| GET / has moved to routes/modules/content.php, where it belongs per
| BUILD_CONTRACT.md §3 — Content\HomeController@index rendering home.blade.php. The
| scaffold placeholder that stood here is deleted, exactly as the comment on it asked.
*/

/*
| Authentication - Faain, after the §0 spike (hand-written Auth::attempt() controllers,
| not a starter kit). login / logout / register go here, not in a module file, because
| every module's middleware depends on them.
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// Module route files - one per member. Add nothing here but require lines.
require __DIR__ . '/modules/admin.php';      // Faain   - Auth, Roles & Admin
require __DIR__ . '/modules/hotel.php';      // Raafil  - Hotel
require __DIR__ . '/modules/ferry.php';      // Naayif  - Ferry
require __DIR__ . '/modules/park.php';       // Malaaz  - Theme Park & Beach
require __DIR__ . '/modules/content.php';    // Safhaan - Content, Map & Reporting
