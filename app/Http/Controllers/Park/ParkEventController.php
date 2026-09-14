<?php

namespace App\Http\Controllers\Park;

use App\Http\Controllers\Controller;
use App\Models\ParkActivity;
use App\Models\ParkEvent;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * What a visitor sees: which rides, shows and beach events run on which day, and what is
 * still available. Public — no auth, because the brief asks for browsing before buying.
 *
 * BUILD_CONTRACT.md §3, Module 4. MASTER_SCHEMA.md §13.
 * Staff scheduling is StaffEventController; this pair is read-only.
 */
class ParkEventController extends Controller
{
    /**
     * Event listing, filtered by date and activity type.
     * GET /park/events → park.events.index
     * Role: public
     *
     * Only bookable events appear: scheduled, today or later, not sold out. A cancelled or
     * finished event is not something a visitor can act on, so showing it only invites a
     * click that ends in a rejection.
     */
    public function index(Request $request): View
    {
        $query = ParkEvent::with('activity')
            ->bookable()
            ->orderBy('event_date')
            ->orderBy('start_time');

        if ($request->filled('date')) {
            $query->onDate($request->string('date')->toString());
        }

        if ($request->filled('type')) {
            $type = $request->string('type')->toString();
            $query->whereHas('activity', fn ($q) => $q->where('type', $type));
        }

        // Hide anything the catalogue has deactivated, whatever is scheduled against it.
        $query->whereHas('activity', fn ($q) => $q->active());

        $events = $query->paginate(12)->withQueryString();

        // The contract specifies each event carries seats_remaining. It is derived from
        // capacity - seats_taken (BR-06) and never stored, so it is attached here rather
        // than read from a column.
        $events->through(function (ParkEvent $event) {
            $event->setAttribute('seats_remaining', $event->seatsRemaining());

            return $event;
        });

        $filters = $request->only(['date', 'type']);

        return view('park.events.index', compact('events', 'filters'));
    }

    /**
     * Event detail and the buy form.
     * GET /park/events/{event} → park.events.show
     * Role: public — the purchase itself requires a visitor login (POST /park/tickets).
     */
    public function show(ParkEvent $event): View
    {
        $event->load('activity.mapLocation');

        $seatsRemaining = $event->seatsRemaining();

        // Other dates for the same activity, so a visitor who arrives at a sold-out event
        // has somewhere to go other than the back button.
        $alternatives = ParkEvent::with('activity')
            ->where('park_activity_id', $event->park_activity_id)
            ->where('id', '!=', $event->id)
            ->bookable()
            ->orderBy('event_date')
            ->limit(5)
            ->get();

        return view('park.events.show', compact('event', 'seatsRemaining', 'alternatives'));
    }
}
