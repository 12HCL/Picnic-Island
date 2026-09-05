<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Redirect each role to its module dashboard when that route is available.
     * The three staff route names are provisional until their module owners confirm them;
     * only the visitor and admin route names are fixed by BUILD_CONTRACT.md §3.
     *
     * @var array<string, string>
     */
    private array $dashboardRoutes = [
        'visitor' => 'visitor.bookings.index',
        'hotel_staff' => 'hotel.dashboard',
        'ferry_operator' => 'ferry.dashboard',
        'park_staff' => 'park.dashboard',
        'admin' => 'admin.dashboard',
    ];

    public function index(): RedirectResponse|View
    {
        $user = auth()->user();
        $role = $user->role;
        $routeName = $this->dashboardRoutes[$role->name] ?? null;

        if ($routeName !== null && Route::has($routeName)) {
            return redirect()->route($routeName);
        }

        return view('dashboard', ['role' => $role]);
    }
}
