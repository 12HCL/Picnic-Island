<?php

namespace App\Http\Controllers\Ferry;

use App\Http\Controllers\Controller;
use App\Models\FerrySchedule;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Module 3 — the operator's view of the timetable, with seat occupancy.
 *
 * The visitor-facing list in FerryScheduleController shows only future sailings that are
 * still open. This one shows every sailing in every status, because an operator needs to
 * see what departed and what was cancelled, not only what is on sale.
 */
class StaffScheduleController extends Controller
{
    /**
     * GET /staff/ferry/schedules — BUILD_CONTRACT.md §3, module 3.
     */
    public function index(Request $request): View
    {
        $schedules = FerrySchedule::query()
            ->with('route', 'vessel')
            ->withCount(['tickets as issued_tickets_count' => fn ($query) => $query->where('status', 'issued')])
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString()),
            )
            ->when(
                $request->filled('date'),
                fn ($query) => $query->whereDate('departure_date', $request->date('date')),
            )
            ->orderByDesc('departure_date')
            ->orderBy('departure_time')
            ->paginate(20)
            ->withQueryString();

        return view('ferry.staff.schedules.index', ['schedules' => $schedules]);
    }
}
