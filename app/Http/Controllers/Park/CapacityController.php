<?php

namespace App\Http\Controllers\Park;

use App\Http\Controllers\Controller;
use App\Models\ParkEvent;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The capacity monitoring dashboard — "capacity limits and monitoring" in the brief.
 * BUILD_CONTRACT.md §3, Module 4. BR-06.
 *
 * The contract specifies the view receives a Collection of events each carrying capacity,
 * seats_taken and percent_full, plus the date being viewed.
 *
 * This screen only reports. It is deliberately not where BR-06 is enforced — a number on a
 * dashboard is stale the moment it is rendered, and the rule lives under a row lock in
 * TicketSalesService. What this gives staff is the thing a dashboard is actually for:
 * seeing which events are filling up before they sell out.
 */
class CapacityController extends Controller
{
    /**
     * GET /staff/park/capacity → park.staff.capacity
     * Role: park_staff
     */
    public function index(Request $request): View
    {
        $date = $request->filled('date')
            ? $request->string('date')->toString()
            : now()->toDateString();

        $events = ParkEvent::with('activity')
            ->onDate($date)
            ->orderBy('start_time')
            ->get();

        // Attached rather than stored: percent_full is derived from two columns and would
        // be a third copy of the same fact if it lived in the table (hard rule 4).
        $events->each(function (ParkEvent $event) {
            $event->setAttribute('percent_full', $event->percentFull());
            $event->setAttribute('seats_remaining', $event->seatsRemaining());
        });

        $scheduled = $events->where('status', 'scheduled');

        return view('park.staff.capacity', [
            'events' => $events,
            'date' => $date,
            'totalCapacity' => $scheduled->sum('capacity'),
            'totalTaken' => $scheduled->sum('seats_taken'),
            // Events at or above 90% — the ones worth a staff member's attention today.
            'nearlyFull' => $scheduled->filter(fn ($e) => $e->percent_full >= 90)->count(),
            'soldOut' => $scheduled->filter(fn ($e) => $e->seats_remaining < 1)->count(),
        ]);
    }
}
