<?php

namespace App\Http\Controllers\Park;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * UC-16 — on-site ticket validation. BUILD_CONTRACT.md §3, Module 4.
 *
 * Staff enter a reference at the gate; the system decides whether to admit. The decision is
 * made here on the server and shown as a verdict — the screen never simply trusts that a
 * ticket presented to it is good.
 *
 * Three outcomes, matching the use case:
 *   admitted  — main flow: valid, today's event, not yet used. Marked used.
 *   rejected  — E1: already used. The prior timestamp is shown, because the visitor will
 *               ask, and "used at 10:42" is the answer that ends the conversation.
 *   rejected  — E2: wrong date, or a cancelled event.
 *
 * Frequency of use is 500–2000 a day, so the screen is built to be usable one-handed with a
 * queue waiting: the reference field keeps focus, and the verdict is a colour before it is
 * a sentence.
 */
class TicketValidationController extends Controller
{
    /**
     * The validation terminal, and the result of the last check.
     * GET /staff/park/validate → park.staff.validate
     * Role: park_staff
     *
     * A1: when the visitor cannot produce a reference, staff search by name instead. The
     * search covers today's events only — a name search across all time would return last
     * month's tickets and invite admitting one.
     */
    public function index(Request $request): View
    {
        $matches = collect();
        $search = $request->string('name')->toString();

        if ($search !== '') {
            $matches = Ticket::with(['event.activity', 'user'])
                ->whereHas('event', fn ($q) => $q->whereDate('event_date', now()->toDateString()))
                ->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                ->orderBy('reference')
                ->limit(20)
                ->get();
        }

        return view('park.staff.validate', [
            'matches' => $matches,
            'search' => $search,
            // Flashed by validate() — the verdict for the ticket just checked.
            'result' => session('validation'),
        ]);
    }

    /**
     * Check a reference and admit or refuse.
     * POST /staff/park/validate → back to the terminal
     * Role: park_staff
     *
     * The verdict is flashed rather than rendered directly so a refresh cannot re-submit
     * the same reference and re-validate a ticket. A2, batch validation, falls out of this:
     * the field is empty and focused again on return, so the next party member's reference
     * goes straight in without leaving the screen.
     */
    public function validateTicket(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:20'],
        ]);

        $reference = trim($validated['reference']);
        $ticket = Ticket::with('event.activity')->where('reference', $reference)->first();

        if (! $ticket) {
            return back()->with('validation', [
                'verdict' => 'rejected',
                'reason' => "No ticket found with reference {$reference}.",
                'ticket' => null,
            ]);
        }

        /**
         * Flash plain values, never the model. Flash data is serialised into the session —
         * `database` here, `array` under test — and an Eloquent model does not survive that
         * round trip intact: it comes back as an array and the view's `$t->reference` dies
         * with "attempt to read property on array". The view needs five fields, so it is
         * given five fields.
         */
        $summary = fn (Ticket $t): array => [
            'reference' => $t->reference,
            'activity' => $t->event->activity->name,
            'event_date' => $t->event->event_date->format('D j M Y'),
            'quantity' => $t->quantity,
            'status' => $t->status,
        ];

        // E1 — already used. Show when, because that is the question that follows.
        if ($ticket->status === 'used') {
            return back()->with('validation', [
                'verdict' => 'rejected',
                'reason' => 'Already used at ' . $ticket->validated_at?->format('H:i \o\n D j M') . '. Deny entry.',
                'ticket' => $summary($ticket),
            ]);
        }

        if ($ticket->status === 'cancelled') {
            return back()->with('validation', [
                'verdict' => 'rejected',
                'reason' => 'This ticket was cancelled. Deny entry.',
                'ticket' => $summary($ticket),
            ]);
        }

        // E2 — wrong date. Checked against the event, not against when the ticket was sold.
        if (! $ticket->event->event_date->isToday()) {
            return back()->with('validation', [
                'verdict' => 'rejected',
                'reason' => 'This ticket is for ' . $ticket->event->event_date->format('D j M Y')
                    . ', not today. Redirect the visitor.',
                'ticket' => $summary($ticket),
            ]);
        }

        // E2 — the event itself is off.
        if ($ticket->event->status === 'cancelled') {
            return back()->with('validation', [
                'verdict' => 'rejected',
                'reason' => 'This event was cancelled. Redirect the visitor.',
                'ticket' => $summary($ticket),
            ]);
        }

        $ticket->update([
            'status' => 'used',
            'validated_at' => now(),
        ]);

        return back()->with('validation', [
            'verdict' => 'admitted',
            'reason' => 'Admit ' . $ticket->quantity . ' to ' . $ticket->event->activity->name . '.',
            'ticket' => $summary($ticket->fresh(['event.activity'])),
        ]);
    }
}
