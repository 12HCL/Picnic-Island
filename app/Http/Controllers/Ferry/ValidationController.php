<?php

namespace App\Http\Controllers\Ferry;

use App\Http\Controllers\Controller;
use App\Models\FerryTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Module 3 — boarding validation at the jetty. UC-14's companion: the operator scans or
 * types a reference and is told whether this pass may board this sailing today.
 *
 * The view-data contract is BUILD_CONTRACT.md §3: 'result' is null before the operator has
 * searched, otherwise ticket, hotelBooking, valid and reason.
 *
 * Marking a pass as boarded is a separate, explicit action. A lookup that silently mutated
 * the ticket would make an operator's mistyped reference impossible to undo.
 */
class ValidationController extends Controller
{
    /**
     * GET /staff/ferry/validate — the empty search form.
     */
    public function index(): View
    {
        return view('ferry.staff.validate', ['result' => null]);
    }

    /**
     * POST /staff/ferry/validate — look one up.
     */
    public function check(Request $request): View
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:20'],
        ]);

        $ticket = FerryTicket::query()
            ->with('schedule.route', 'schedule.vessel', 'hotelBooking.hotel', 'user')
            ->where('reference', $validated['reference'])
            ->first();

        return view('ferry.staff.validate', [
            'result' => $this->assess($ticket, $validated['reference']),
        ]);
    }

    /**
     * POST /staff/ferry/validate/{ticket}/board — record that the passenger boarded.
     *
     * Re-runs the full check rather than trusting that the operator only sees this button
     * on a valid pass. Hiding the button is a courtesy; the refusal has to be a server
     * decision, for the same reason BR-01 is enforced in the controller and not by
     * withholding the booking form.
     */
    public function board(FerryTicket $ticket): RedirectResponse
    {
        $ticket->load('schedule');

        $reason = $this->reasonNotBoardable($ticket);

        if ($reason !== null) {
            return back()->with('error', "Ticket {$ticket->reference} cannot be boarded. {$reason}");
        }

        $ticket->update(['status' => 'boarded']);

        return back()->with('success', "Ticket {$ticket->reference} marked as boarded.");
    }

    /**
     * Why a pass is or is not good for travel. Kept in one place so the reason shown to the
     * operator and the decision itself can never disagree.
     *
     * @return array{ticket: ?FerryTicket, hotelBooking: ?\App\Models\HotelBooking, valid: bool, reason: ?string}
     */
    private function assess(?FerryTicket $ticket, string $reference): array
    {
        if ($ticket === null) {
            return [
                'ticket' => null,
                'hotelBooking' => null,
                'valid' => false,
                'reason' => "No ferry ticket found with reference {$reference}.",
            ];
        }

        $reason = $this->reasonNotBoardable($ticket);

        return [
            'ticket' => $ticket,
            'hotelBooking' => $ticket->hotelBooking,
            'valid' => $reason === null,
            'reason' => $reason,
        ];
    }

    /**
     * Why this pass may not board, or null when it may.
     *
     * The single source of truth for both the lookup and the boarding action, so the reason
     * shown to the operator and the decision the server actually makes can never disagree.
     */
    private function reasonNotBoardable(FerryTicket $ticket): ?string
    {
        return match (true) {
            $ticket->status === 'cancelled' => 'This ticket has been cancelled.',
            $ticket->status === 'boarded' => 'This ticket has already been used to board.',
            ! $ticket->schedule->departure_date->isToday() => 'This ticket is for '
                .$ticket->schedule->departure_date->format('j F Y').', not today.',
            $ticket->schedule->status === 'cancelled' => 'This sailing has been cancelled.',
            default => null,
        };
    }
}
