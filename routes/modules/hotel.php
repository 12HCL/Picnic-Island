<?php

/*
|--------------------------------------------------------------------------
| Module 2 - Hotel
|--------------------------------------------------------------------------
| Owner: Ahmed Raafil. This file is yours alone - nobody else edits it, and you
| edit no one else's. That is the whole point of the split (BUILD_CONTRACT.md §2):
| five people in one routes/web.php is the commonest merge conflict on a
| five-person Laravel project.
|
| It is loaded by routes/web.php, so anything you add here is live immediately.
|
| Conventions (BUILD_CONTRACT.md §2) - derive names, do not invent them:
|   URL          kebab-case, plural       /hotel/rooms
|   Route name   dot-separated, matches   hotel.rooms.index
|   Controller   App\Http\Controllers\Hotel\...
|   View         resources/views/hotel/<resource>/<action>.blade.php
|
| Role middleware is Faain's and applies to every module:
|   Route::middleware(['auth', 'role:hotel_staff'])->group(function () { ... });
| The five role names are visitor, hotel_staff, ferry_operator, park_staff, admin,
| exactly as seeded in `roles`.`name`. Nobody invents a sixth.
|
| Your routes are listed in BUILD_CONTRACT.md §3 - that table is the contract.
*/

use App\Http\Controllers\Hotel\AvailabilityController;
use App\Http\Controllers\Hotel\HotelBookingController;
use App\Http\Controllers\Hotel\HotelController;
use App\Http\Controllers\Hotel\HotelReportController;
use App\Http\Controllers\Hotel\PaymentController;
use App\Http\Controllers\Hotel\RoomController;
use App\Http\Controllers\Hotel\StaffBookingController;
use App\Http\Controllers\Hotel\StaffDashboardController;
use Illuminate\Support\Facades\Route;

// ── Public (no auth) ─────────────────────────────────────────────────────────
Route::get('/hotels', [HotelController::class, 'index'])->name('hotel.index');
Route::get('/hotels/{hotel}', [HotelController::class, 'show'])->name('hotel.show');

// ── Visitor — booking flow ────────────────────────────────────────────────────
Route::middleware(['auth', 'role:visitor'])->group(function () {
    Route::get('/hotel-bookings/create', [HotelBookingController::class, 'create'])
        ->name('hotel.bookings.create');
    Route::post('/hotel-bookings', [HotelBookingController::class, 'store'])
        ->name('hotel.bookings.store');

    // Payment (simulated)
    Route::get('/hotel-bookings/{booking}/pay', [PaymentController::class, 'create'])
        ->name('hotel.bookings.pay');
    Route::post('/hotel-bookings/{booking}/pay', [PaymentController::class, 'store'])
        ->name('hotel.bookings.pay.store');
});

// ── Visitor OR hotel_staff — booking show (visitor sees own; staff sees any) ──
Route::middleware(['auth'])->group(function () {
    Route::get('/hotel-bookings/{booking}', [HotelBookingController::class, 'show'])
        ->name('hotel.bookings.show');
});

// ── AJAX availability (auth only — session-based, must NOT go in api.php) ─────
Route::middleware(['auth'])->group(function () {
    Route::get('/ajax/hotel/availability', [AvailabilityController::class, 'show'])
        ->name('ajax.hotel.availability');
});

// ── Hotel staff ───────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:hotel_staff'])->group(function () {

    // Staff dashboard — named 'hotel.dashboard' to match DashboardController's redirect map
    Route::get('/staff/hotel', [StaffDashboardController::class, 'index'])
        ->name('hotel.dashboard');

    // Staff booking management
    Route::get('/staff/hotel/bookings', [StaffBookingController::class, 'index'])
        ->name('hotel.staff.bookings.index');
    Route::post('/staff/hotel/bookings/{booking}/confirm', [StaffBookingController::class, 'confirm'])
        ->name('hotel.staff.bookings.confirm');
    Route::post('/staff/hotel/bookings/{booking}/check-in', [StaffBookingController::class, 'checkIn'])
        ->name('hotel.staff.bookings.check-in');
    Route::post('/staff/hotel/bookings/{booking}/check-out', [StaffBookingController::class, 'checkOut'])
        ->name('hotel.staff.bookings.check-out');
    Route::post('/staff/hotel/bookings/{booking}/cancel', [StaffBookingController::class, 'cancel'])
        ->name('hotel.staff.bookings.cancel');

    // Booking edit/update (staff can correct dates, guests, status)
    Route::get('/hotel-bookings/{booking}/edit', [HotelBookingController::class, 'edit'])
        ->name('hotel.bookings.edit');
    Route::put('/hotel-bookings/{booking}', [HotelBookingController::class, 'update'])
        ->name('hotel.bookings.update');
    Route::post('/hotel-bookings/{booking}/cancel', [HotelBookingController::class, 'cancel'])
        ->name('hotel.bookings.cancel');

    // Room CRUD — full resource, destroy allowed (no payments FK points at rooms)
    Route::resource('staff/hotel/rooms', RoomController::class)->names([
        'index'   => 'hotel.staff.rooms.index',
        'create'  => 'hotel.staff.rooms.create',
        'store'   => 'hotel.staff.rooms.store',
        'show'    => 'hotel.staff.rooms.show',
        'edit'    => 'hotel.staff.rooms.edit',
        'update'  => 'hotel.staff.rooms.update',
        'destroy' => 'hotel.staff.rooms.destroy',
    ]);

    // Reports
    Route::get('/staff/hotel/reports', [HotelReportController::class, 'index'])
        ->name('hotel.staff.reports.index');
});
