<?php

namespace App\Http\Controllers\Park;

use App\Http\Controllers\Controller;
use App\Models\ParkEvent;
use App\Models\Ticket;
use Illuminate\View\View;

/**
 * Where park staff land after logging in. BUILD_CONTRACT.md §3, Module 4.
 *
 * Route name park.dashboard, not park.staff.dashboard: DashboardController maps park_staff
 * to 'park.dashboard' and only redirects when Route::has() finds it. Until this existed a
 * park_staff login fell through to the generic dashboard view, and /staff/park was one of
 * the 404s the 12 September QA sweep logged.
 */
class StaffDashboardController extends Controller
{
    /**
     * GET /staff/park → park.dashboard
     * Role: park_staff
     */
    public function index(): View
    {
        $today = now()->toDateString();

        $todaysEvents = ParkEvent::with('activity')
            ->onDate($today)
            ->where('status', 'scheduled')
            ->orderBy('start_time')
            ->get();

        $ticketsToday = Ticket::whereHas('event', fn ($q) => $q->whereDate('event_date', $today));

        return view('park.staff.dashboard', [
            'todaysEvents' => $todaysEvents,
            'eventCount' => $todaysEvents->count(),
            'admissionsSold' => (clone $ticketsToday)->valid()->sum('quantity'),
            'admissionsUsed' => (clone $ticketsToday)->where('status', 'used')->sum('quantity'),
            // Revenue is grouped by channel, never by user_id, because a gate sale has no
            // buyer to group by (MASTER_SCHEMA.md §14).
            'onlineToday' => (clone $ticketsToday)->channel('online')->where('status', '!=', 'cancelled')->count(),
            'gateToday' => (clone $ticketsToday)->channel('gate')->where('status', '!=', 'cancelled')->count(),
        ]);
    }
}
