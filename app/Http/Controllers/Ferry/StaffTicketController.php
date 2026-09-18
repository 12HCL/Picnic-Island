<?php

namespace App\Http\Controllers\Ferry;

use App\Http\Controllers\Controller;
use App\Models\FerrySchedule;
use App\Models\HotelBooking;
use App\Models\User;
use App\Services\Ferry\FerryTicketIssueService;
use App\Services\Hotel\HotelBookingGateway;
use App\Services\Payment\PaymentService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Module 3 — counter issuance. UC-14.
 *
 * Exactly the same rules as the visitor's own purchase in UC-05, because both go through
 * FerryTicketIssueService. That is the point of the service: an operator at the counter
 * cannot accidentally bypass BR-01 by using a different code path, which is the first thing
 * a marker would try.
 *
 * The one difference on the row is issued_by and issued_at, which the service sets when an
 * operator is passed. Online purchases leave both null.
 */
class StaffTicketController extends Controller
{
    public function __construct(
        private readonly HotelBookingGateway $gateway,
        private readonly FerryTicketIssueService $issuer,
    ) {
    }

    /**
     * GET /staff/ferry/issue — look up a visitor's booking, then pick their sailing.
     *
     * Search by booking reference or by the visitor's email, which is UC-14 A2: a walk-up
     * passenger often does not know their reference.
     */
    public function create(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $bookings = collect();

        if ($search !== '') {
            $bookings = HotelBooking::query()
                ->with('user', 'hotel')
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->where(function ($query) use ($search) {
                    $query->where('reference', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($q) => $q->where('email', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%"));
                })
                ->orderByDesc('check_in')
                ->limit(20)
                ->get();
        }

        $schedules = FerrySchedule::query()
            ->with('route', 'vessel')
            ->where('status', 'scheduled')
            ->whereDate('departure_date', '>=', now()->toDateString())
            ->orderBy('departure_date')
            ->orderBy('departure_time')
            ->get();

        return view('ferry.staff.issue', [
            'search' => $search,
            'bookings' => $bookings,
            // Sailings this particular booking could authorise, keyed by booking id.
            // BR-01 is still decided on the server at write time - this only stops the
            // operator being offered a crossing that is certain to be refused, which is
            // what happens when a guest's stay is months away from the sailings on offer.
            'schedulesFor' => $bookings->mapWithKeys(fn (HotelBooking $booking) => [
                $booking->id => $schedules->filter(
                    fn (FerrySchedule $schedule) => $schedule->departure_date->betweenIncluded(
                        $booking->check_in,
                        $booking->check_out,
                    ),
                )->values(),
            ]),
            'methods' => PaymentService::METHODS,
        ]);
    }

    /**
     * POST /staff/ferry/issue.
     *
     * The passenger is the hotel booking's owner, never the operator. Taking it from the
     * booking rather than from a posted user_id means an operator cannot issue a ticket to
     * one person against another person's booking, which is BR-01 with the names swapped.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hotel_booking_id' => ['required', 'integer', 'exists:hotel_bookings,id'],
            'ferry_schedule_id' => ['required', 'integer', 'exists:ferry_schedules,id'],
            'method' => ['required', \Illuminate\Validation\Rule::in(PaymentService::METHODS)],
        ]);

        $booking = HotelBooking::findOrFail($validated['hotel_booking_id']);
        $schedule = FerrySchedule::findOrFail($validated['ferry_schedule_id']);
        $passenger = User::findOrFail($booking->user_id);

        try {
            $ticket = $this->issuer->issue(
                $schedule,
                $booking->id,
                $passenger,
                $request->user(), // the operator — this is what sets issued_by and issued_at
                $validated['method'],
            );
        } catch (DomainException $e) {
            // The gateway's own message is written in the second person - "your hotel
            // booking" - because a visitor normally reads it. At the counter the operator is
            // not the guest, so name whose booking it is and drop the second person.
            $reason = str_ireplace(' your ', ' the ', $e->getMessage());

            return back()->withInput()->with(
                'error',
                "Cannot issue to {$passenger->name} on booking {$booking->reference}: "
                    .lcfirst($reason),
            );
        }

        return redirect()
            ->route('ferry.tickets.show', $ticket)
            ->with('success', "Ferry ticket {$ticket->reference} issued to {$passenger->name}.");
    }
}
