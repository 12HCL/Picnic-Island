<?php

namespace App\Http\Controllers\Park;

use App\Http\Controllers\Controller;
use App\Http\Requests\Park\StoreTicketRequest;
use App\Models\ParkEvent;
use App\Models\Ticket;
use App\Services\Park\TicketSalesService;
use App\Services\Payment\PaymentService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Module 4 — online ticket sales. BUILD_CONTRACT.md §3.
 *
 * There is no destroy method and no DELETE route. Voiding a ticket is status = cancelled
 * through a named POST, because a deleted row vanishes from the sales reports and orphans
 * the payment attached to it.
 *
 * The sale itself lives in TicketSalesService — BR-06 is a locked transaction and belongs
 * in one place, since the gate channel has to obey exactly the same rule.
 */
class TicketController extends Controller
{
    public function __construct(private readonly TicketSalesService $sales)
    {
    }

    /**
     * The confirmation screen: what is being bought, for how much, and by which method.
     * GET /park/tickets/create?park_event_id=&quantity= → park.tickets.create
     * Role: visitor
     *
     * MASTER_SCHEMA.md §14 puts the tickets row at payment confirmation, so nothing is
     * written until store(). This screen is the simulated payment step the brief asks for.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $event = ParkEvent::with('activity')->find($request->integer('park_event_id'));

        if (! $event) {
            return redirect()->route('park.events.index')
                ->with('error', 'Choose an event before continuing to payment.');
        }

        $quantity = max(1, min(10, $request->integer('quantity', 1)));
        $seatsRemaining = $event->seatsRemaining();

        if ($seatsRemaining < $quantity) {
            return redirect()->route('park.events.show', $event)
                ->with('error', $seatsRemaining === 0
                    ? 'This event sold out while you were deciding.'
                    : "Only {$seatsRemaining} admission(s) left for this event.");
        }

        return view('park.tickets.create', [
            'event' => $event,
            'quantity' => $quantity,
            'methods' => PaymentService::METHODS,
        ]);
    }

    /**
     * Buy admissions and pay for them, in one transaction.
     * POST /park/tickets → redirect to park.tickets.show
     * Role: visitor
     *
     * A DomainException here is the expected refusal — sold out, event cancelled, event
     * past — not a fault. It is caught and shown, because the visitor did nothing wrong;
     * somebody else simply got the last seat first.
     */
    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $event = ParkEvent::findOrFail($request->integer('park_event_id'));

        try {
            $ticket = $this->sales->sell(
                event: $event,
                quantity: $request->integer('quantity'),
                channel: 'online',
                buyer: $request->user(),
                seller: null,
                method: $request->string('method')->toString(),
            );
        } catch (DomainException $e) {
            return redirect()->route('park.events.show', $event)
                ->with('error', $e->getMessage());
        }

        return redirect()->route('park.tickets.show', $ticket)
            ->with('success', "Paid. Your reference is {$ticket->reference}.");
    }

    /**
     * A ticket, as the buyer's proof of purchase and as staff's lookup.
     * GET /park/tickets/{ticket} → park.tickets.show
     * Role: visitor (own only), park_staff (any)
     *
     * The ownership check is explicit rather than a role check alone. QA finding #1 on
     * 12 September was exactly this on hotel bookings: every logged-in role could read a
     * stranger's booking, including their name and dates. A ticket carries less, but the
     * fix is the same shape and belongs here from the start.
     */
    public function show(Request $request, Ticket $ticket): View
    {
        $user = $request->user();
        $isStaff = $user->role?->name === 'park_staff';
        $isOwner = $ticket->user_id !== null && $ticket->user_id === $user->id;

        if (! $isStaff && ! $isOwner) {
            throw new AccessDeniedHttpException('This ticket belongs to someone else.');
        }

        $ticket->load(['event.activity', 'soldBy', 'payment']);

        return view('park.tickets.show', compact('ticket'));
    }

    /**
     * Void a ticket and return its seats to the event.
     * POST /park/tickets/{ticket}/cancel → back
     * Role: visitor (own only), park_staff (any)
     */
    public function cancel(Request $request, Ticket $ticket): RedirectResponse
    {
        $user = $request->user();
        $isStaff = $user->role?->name === 'park_staff';
        $isOwner = $ticket->user_id !== null && $ticket->user_id === $user->id;

        if (! $isStaff && ! $isOwner) {
            throw new AccessDeniedHttpException('This ticket belongs to someone else.');
        }

        try {
            $this->sales->cancel($ticket);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Ticket {$ticket->reference} cancelled.");
    }
}
