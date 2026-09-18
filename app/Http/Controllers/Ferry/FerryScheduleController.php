<?php

namespace App\Http\Controllers\Ferry;

use App\Http\Controllers\Controller;
use App\Models\FerryRoute;
use App\Models\FerrySchedule;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Module 3 — Ferry. Public sailing timetable.
 *
 * Deliberately public, like module 4's event listing: the brief asks a visitor to see what
 * is sailing before they are asked to log in. Booking is where authentication starts, and
 * BR-01 is checked after that, on the booking page.
 */
class FerryScheduleController extends Controller
{
    /**
     * GET /ferry/schedules — BUILD_CONTRACT.md §3, module 3.
     */
    public function index(Request $request): View
    {
        $schedules = FerrySchedule::query()
            ->with('route', 'vessel')
            ->where('status', 'scheduled')
            ->whereDate('departure_date', '>=', now()->toDateString())
            ->when(
                $request->filled('route'),
                fn ($query) => $query->where('ferry_route_id', $request->integer('route')),
            )
            ->when(
                $request->filled('date'),
                fn ($query) => $query->whereDate('departure_date', $request->date('date')),
            )
            ->orderBy('departure_date')
            ->orderBy('departure_time')
            ->paginate(15)
            ->withQueryString();

        return view('ferry.schedules.index', [
            'schedules' => $schedules,
            'routes' => FerryRoute::orderBy('origin')->get(),
        ]);
    }
}
