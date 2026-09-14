<?php

namespace App\Http\Controllers\Park;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Sales and visitor reports for Module 4. BUILD_CONTRACT.md §3.
 *
 * Reported over the **event date**, not the sale date. A report of what the park took last
 * week is less useful to park staff than a report of what ran last week — rostering, capacity
 * planning and "how did the Dolphin Show do" are all questions about when things happened,
 * not when they were paid for.
 *
 * Revenue is grouped by channel, never by user_id: an anonymous gate sale has no buyer to
 * group by (MASTER_SCHEMA.md §14). Aggregates are computed in SQL with expressions that mean
 * the same thing on MySQL and on SQLite, because the test suite runs on one and the
 * demonstration runs on the other.
 */
class ParkReportController extends Controller
{
    /**
     * GET /staff/park/reports → park.staff.reports.index
     * Role: park_staff
     */
    public function index(Request $request): View
    {
        // Default window: the month to date. Long enough to be worth reading, short enough
        // that an empty database does not render a year of zeroes.
        $from = $request->filled('from')
            ? $request->string('from')->toString()
            : now()->startOfMonth()->toDateString();

        $to = $request->filled('to')
            ? $request->string('to')->toString()
            : now()->endOfMonth()->toDateString();

        $channel = $request->string('channel')->toString();

        // unit_price x quantity, not the event price joined at read time: a ticket carries
        // what it was actually sold for, so repricing an event cannot rewrite history.
        $revenue = 'SUM(tickets.unit_price * tickets.quantity)';

        // whereDate on both bounds, not whereBetween on the raw column. MySQL stores a DATE
        // column as 2026-09-14, but SQLite keeps what Laravel's date cast writes —
        // 2026-09-14 00:00:00 — and a string comparison against an upper bound of
        // 2026-09-14 then excludes that whole day. whereDate casts in SQL on both drivers,
        // so "to: today" includes today's sales either way.
        $base = fn (): Builder => Ticket::query()
            ->join('park_events', 'park_events.id', '=', 'tickets.park_event_id')
            ->whereDate('park_events.event_date', '>=', $from)
            ->whereDate('park_events.event_date', '<=', $to)
            ->when($channel !== '', fn ($q) => $q->where('tickets.channel', $channel));

        // Sold = everything that still stands. Cancelled tickets are reported separately
        // rather than folded in, because they are money that was taken and now needs
        // refunding — netting them off silently would hide that.
        $sold = fn (): Builder => $base()->where('tickets.status', '!=', 'cancelled');

        $summary = $sold()
            ->selectRaw('COUNT(*) as tickets')
            ->selectRaw('COALESCE(SUM(tickets.quantity), 0) as admissions')
            ->selectRaw("COALESCE({$revenue}, 0) as revenue")
            ->first();

        $cancelled = $base()->where('tickets.status', 'cancelled')
            ->selectRaw('COUNT(*) as tickets')
            ->selectRaw('COALESCE(SUM(tickets.quantity), 0) as admissions')
            ->selectRaw("COALESCE({$revenue}, 0) as revenue")
            ->first();

        $admitted = $base()->where('tickets.status', 'used')
            ->selectRaw('COALESCE(SUM(tickets.quantity), 0) as admissions')
            ->value('admissions');

        $byChannel = $sold()
            ->selectRaw('tickets.channel')
            ->selectRaw('COUNT(*) as tickets')
            ->selectRaw('COALESCE(SUM(tickets.quantity), 0) as admissions')
            ->selectRaw("COALESCE({$revenue}, 0) as revenue")
            ->groupBy('tickets.channel')
            ->orderBy('tickets.channel')
            ->get();

        $byActivity = $sold()
            ->join('park_activities', 'park_activities.id', '=', 'park_events.park_activity_id')
            ->selectRaw('park_activities.name as activity')
            ->selectRaw('park_activities.type as type')
            ->selectRaw('COUNT(*) as tickets')
            ->selectRaw('COALESCE(SUM(tickets.quantity), 0) as admissions')
            ->selectRaw("COALESCE({$revenue}, 0) as revenue")
            ->groupBy('park_activities.id', 'park_activities.name', 'park_activities.type')
            ->orderByDesc(DB::raw($revenue))
            ->get();

        $byDay = $sold()
            ->selectRaw('park_events.event_date as day')
            ->selectRaw('COALESCE(SUM(tickets.quantity), 0) as admissions')
            ->selectRaw("COALESCE({$revenue}, 0) as revenue")
            ->groupBy('park_events.event_date')
            ->orderBy('park_events.event_date')
            ->get();

        $soldAdmissions = (int) ($summary->admissions ?? 0);

        return view('park.staff.reports.index', [
            'from' => $from,
            'to' => $to,
            'channel' => $channel,
            'tickets' => (int) ($summary->tickets ?? 0),
            'admissions' => $soldAdmissions,
            'revenue' => (float) ($summary->revenue ?? 0),
            'admitted' => (int) $admitted,
            // Attendance: of the admissions sold, how many were actually validated at the
            // gate. The gap is no-shows, which is a visitor-behaviour number rather than a
            // fault, and the brief asks for visitor reports as well as sales ones.
            'attendanceRate' => $soldAdmissions > 0
                ? round($admitted / $soldAdmissions * 100, 1)
                : 0.0,
            'cancelledTickets' => (int) ($cancelled->tickets ?? 0),
            'cancelledValue' => (float) ($cancelled->revenue ?? 0),
            'byChannel' => $byChannel,
            'byActivity' => $byActivity,
            'byDay' => $byDay,
        ]);
    }
}
