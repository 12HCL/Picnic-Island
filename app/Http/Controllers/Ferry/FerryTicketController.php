<?php

namespace App\Http\Controllers\Ferry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ferry\StoreFerryTicketRequest;
use App\Models\FerrySchedule;
use App\Models\FerryTicket;
use App\Services\Ferry\FerryTicketIssueService;
use App\Services\Hotel\HotelBookingGateway;
use App\Services\Payment\PaymentService;
use DomainException;
use Illuminate\Http\RedirectResponse;
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
 *   2. Service   — HotelBookingGateway, called on create() and again, under a row lock,
 *                  inside FerryTicketIssueService::issue().
 *   3. Request   — StoreFerryTicketRequest.
 *
 * The refusal is a server decision. The visitor is not shown a form they may not submit,
 * and hiding a button is never the mechanism.
 */
class FerryTicketController extends Controller
{
    public function __construct(
        private readonly HotelBookingGateway $gateway,
        private readonly FerryTicketIssueService $issuer,
    ) {
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
            'methods' => PaymentService::METHODS,
        ]);
    }

    /**
     * POST /ferry/tickets.
     *
     * Payment is a simulated confirmation, so this is also the pay screen's submit: the
     * ticket row is written only here, at confirmation (MASTER_SCHEMA.md §11). There is no
     * separate /ferry/tickets/{ticket}/pay route because until this method runs there is no
     * ticket to hang one off — the same conclusion module 4 reached for park tickets.
     */
    public function store(StoreFerryTicketRequest $request): RedirectResponse
    {
        $schedule = FerrySchedule::findOrFail($request->integer('ferry_schedule_id'));

        try {
            $ticket = $this->issuer->issue(
                $schedule,
                $request->integer('hotel_booking_id'),
                $request->user(),
                null, // online purchase: no operator, so issued_by and issued_at stay null
                $request->string('method')->toString(),
            );
        } catch (DomainException $e) {
            // BR-01 or BR-02 refused it at write time. Back to the booking page with the
            // reason, which is where the visitor can pick another sailing.
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('ferry.tickets.show', $ticket)
            ->with('success', "Ferry ticket {$ticket->reference} issued.");
    }

    /**
     * GET /ferry/tickets/{ticket} — the passenger's own ticket, or any ticket to a ferry
     * operator. Role middleware cannot express "the owner or this one staff role", so the
     * check is here, in the controller.
     *
     * Written this way deliberately after QA finding #1 on the hotel module, where asking
     * only "is this a visitor who is not the owner?" let every other role fall through.
     * This asks the opposite question: who is allowed, with everyone else refused.
     */
    public function show(Request $request, FerryTicket $ticket): View
    {
        $user = $request->user();

        $isOperator = $user->hasRole('ferry_operator');
        $isOwner = $user->hasRole('visitor') && $ticket->user_id === $user->id;

        abort_unless($isOperator || $isOwner, 403);

        $ticket->load('schedule.route', 'schedule.vessel', 'hotelBooking.hotel', 'user', 'issuedBy', 'payment');

        return view('ferry.tickets.show', ['ticket' => $ticket]);
    }
}
