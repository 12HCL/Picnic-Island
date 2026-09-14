<?php

namespace App\Http\Controllers\Park;

use App\Http\Controllers\Controller;
use App\Http\Requests\Park\StoreGateSaleRequest;
use App\Models\ParkEvent;
use App\Services\Park\TicketSalesService;
use App\Services\Payment\PaymentService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Module 4 — at-entrance ticket sales. BUILD_CONTRACT.md §3.
 *
 * The second of the two channels the brief asks for. A walk-up buyer has no account, so
 * tickets.user_id is null and payments.user_id is null with it (MASTER_SCHEMA.md §14, §17,
 * schema question 2, answered 4 September 2026).
 *
 * Accountability is not lost by that: tickets.sold_by records the staff member who made the
 * sale. The seller never goes in payments.user_id — that column means the buyer — which is
 * why revenue reports group by channel rather than by user.
 *
 * BR-06 is enforced in TicketSalesService, the same code path an online sale takes. Staff
 * standing at the gate get no privileged route around the capacity limit.
 */
class GateSaleController extends Controller
{
    public function __construct(private readonly TicketSalesService $sales)
    {
    }

    /**
     * The till screen: today's events, what is left on each, and a sale form.
     * GET /staff/park/sell → park.staff.gate-sale
     * Role: park_staff
     */
    public function create(Request $request): View
    {
        $date = $request->filled('date')
            ? $request->string('date')->toString()
            : now()->toDateString();

        // Today's sellable events, cheapest lookup for someone with a queue in front of
        // them. Sold-out events stay in the list, greyed out by the view, because "it is
        // full" is an answer the person at the till needs to give.
        $events = ParkEvent::with('activity')
            ->onDate($date)
            ->where('status', 'scheduled')
            ->whereHas('activity', fn ($q) => $q->active())
            ->orderBy('start_time')
            ->get();

        return view('park.staff.gate-sale', [
            'events' => $events,
            'date' => $date,
            'methods' => PaymentService::METHODS,
        ]);
    }

    /**
     * Sell an admission at the gate and take payment for it.
     * POST /staff/park/sell → back to the till
     * Role: park_staff
     *
     * Redirects back to the till rather than to the ticket, because the next customer is
     * already waiting. The reference is in the flash message for the receipt.
     */
    public function store(StoreGateSaleRequest $request): RedirectResponse
    {
        $event = ParkEvent::findOrFail($request->integer('park_event_id'));

        try {
            $ticket = $this->sales->sell(
                event: $event,
                quantity: $request->integer('quantity'),
                channel: 'gate',
                // No buyer: an anonymous walk-up sale, by design.
                buyer: null,
                seller: $request->user(),
                method: $request->string('method')->toString(),
            );
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('park.staff.gate-sale', ['date' => $event->event_date->toDateString()])
            ->with('success', "Sold {$ticket->quantity} x {$event->activity->name} — {$ticket->reference}.");
    }
}
