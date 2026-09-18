<?php

namespace App\Http\Controllers\Ferry;

use App\Http\Controllers\Controller;
use App\Models\FerrySchedule;
use Illuminate\View\View;

/**
 * Module 3 — the passenger list for one sailing. The operational document the crew carries,
 * and the ferry half of the trip reports the requirements ask for.
 *
 * Cancelled tickets are listed rather than hidden. A manifest that quietly omits them
 * cannot be reconciled against seats_taken, and an operator checking a passenger off needs
 * to see that a pass was voided rather than simply not find it.
 */
class ManifestController extends Controller
{
    /**
     * GET /staff/ferry/manifest/{schedule} — BUILD_CONTRACT.md §3, module 3.
     */
    public function show(FerrySchedule $schedule): View
    {
        $schedule->load('route', 'vessel');

        $tickets = $schedule->tickets()
            ->with('user', 'hotelBooking.hotel', 'issuedBy')
            ->orderBy('reference')
            ->get();

        return view('ferry.staff.manifest', [
            'schedule' => $schedule,
            'tickets' => $tickets,
            'issued' => $tickets->where('status', 'issued')->count(),
            'boarded' => $tickets->where('status', 'boarded')->count(),
            'cancelled' => $tickets->where('status', 'cancelled')->count(),
        ]);
    }
}
