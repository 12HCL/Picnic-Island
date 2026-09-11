<?php

namespace App\Http\Controllers\Ferry;

use App\Http\Controllers\Controller;
use App\Models\FerrySchedule;
use App\Services\Hotel\HotelBookingGateway;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Module 3 — Ferry. Owner: Ali Naayif.
 *
 * BR-01, the rule the whole brief turns on: a ferry ticket may only be issued to a visitor
 * who already holds a valid hotel booking covering the sailing date.
 *
 * The rule is enforced in three layers, and this controller is the second of them:
 *   1. Database  — ferry_tickets.hotel_booking_id is NOT NULL.
 *   2. Service   — HotelBookingGateway, called here and again in store().
 *   3. Request   — the form request on store().
 *
 * The refusal below is a server decision. The visitor is not shown a form they are not
 * allowed to submit, and hiding a button is never the mechanism.
 */
class FerryTicketController extends Controller
{
    public function __construct(private readonly HotelBookingGateway $gateway)
    {
    }

    /**
     * GET /ferry/schedules/{schedule}/book — BUILD_CONTRACT.md §3, module 3.
     *
     * $eligibleBookings being empty means the visitor may not travel. The view renders the
     * refusal instead of the form, as a single @if/@else on the whole form.
     */
    public function create(Request $request, FerrySchedule $schedule): View
    {
        $schedule->load('route', 'vessel');

        $eligibleBookings = $this->gateway->validBookingsFor(
            $request->user(),
            $schedule->departure_date->toDateString(),
        );

        return view('ferry.tickets.create', [
            'schedule' => $schedule,
            'eligibleBookings' => $eligibleBookings,
        ]);
    }
}
