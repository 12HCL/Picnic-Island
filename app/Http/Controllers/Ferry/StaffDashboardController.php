<?php

namespace App\Http\Controllers\Ferry;

use App\Http\Controllers\Controller;
use App\Models\FerrySchedule;
use App\Models\FerryTicket;
use Illuminate\View\View;

/**
 * Module 3 — the ferry operator's landing page.
 *
 * Named ferry.dashboard to match DashboardController's per-role redirect map, the same way
 * hotel's and park's are. That map already expects this name, so logging in as a
 * ferry_operator lands here rather than on the placeholder.
 */
class StaffDashboardController extends Controller
{
    /**
     * GET /staff/ferry — BUILD_CONTRACT.md §3, module 3.
     */
    public function index(): View
    {
        $today = now()->toDateString();

        $sailingsToday = FerrySchedule::query()
            ->with('route', 'vessel')
            ->whereDate('departure_date', $today)
            ->orderBy('departure_time')
            ->get();

        return view('ferry.staff.dashboard', [
            'sailingsToday' => $sailingsToday,
            'seatsSoldToday' => $sailingsToday->sum('seats_taken'),
            'ticketsIssuedToday' => FerryTicket::whereDate('created_at', $today)->count(),
            'upcomingSailings' => FerrySchedule::query()
                ->where('status', 'scheduled')
                ->whereDate('departure_date', '>', $today)
                ->count(),
        ]);
    }
}
