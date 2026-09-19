<?php

namespace App\Http\Controllers\Ferry;

use App\Http\Controllers\Controller;
use App\Models\FerrySchedule;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Module 3 — trip reports. Every sailing in a date range with passes sold, boarded,
 * revenue and occupancy. REQUIREMENTS.md role 3 asks for "passenger list and trip
 * reports"; the manifest is the first half, this is the second.
 *
 * Cancelled passes are excluded from sold, revenue and occupancy, because cancelling
 * returns the seat and the fare. They are still counted in their own column so the
 * figures reconcile against the manifest.
 */
class TripReportController extends Controller
{
    /**
     * GET /staff/ferry/reports — ferry.staff.reports.index
     */
    public function index(Request $request): View
    {
        // Validated before $request->date(), which throws on anything Carbon cannot parse.
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now()->addDays(30);

        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        $notCancelled = fn ($q) => $q->where('status', '!=', 'cancelled');

        $sailings = FerrySchedule::query()
            ->with('route', 'vessel')
            ->whereBetween('departure_date', [$from->toDateString(), $to->toDateString()])
            ->withCount([
                'tickets as sold_count' => $notCancelled,
                'tickets as boarded_count' => fn ($q) => $q->where('status', 'boarded'),
                'tickets as cancelled_count' => fn ($q) => $q->where('status', 'cancelled'),
            ])
            ->withSum(['tickets as revenue' => $notCancelled], 'fare')
            ->orderBy('departure_date')
            ->orderBy('departure_time')
            ->get();

        // Occupancy is only meaningful for sailings that ran or will run.
        $operating = $sailings->where('status', '!=', 'cancelled');
        $capacity = $operating->sum(fn ($s) => $s->vessel->capacity);

        $summary = [
            'sailings' => $sailings->count(),
            'sold' => $sailings->sum('sold_count'),
            'boarded' => $sailings->sum('boarded_count'),
            'revenue' => (float) $sailings->sum('revenue'),
            'occupancy' => $capacity > 0 ? round($operating->sum('sold_count') / $capacity * 100) : 0,
        ];

        return view('ferry.staff.reports', [
            'sailings' => $sailings,
            'summary' => $summary,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }
}
