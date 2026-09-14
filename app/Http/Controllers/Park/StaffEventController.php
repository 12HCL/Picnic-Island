<?php

namespace App\Http\Controllers\Park;

use App\Http\Controllers\Controller;
use App\Http\Requests\Park\StoreParkEventRequest;
use App\Http\Requests\Park\UpdateParkEventRequest;
use App\Models\ParkActivity;
use App\Models\ParkEvent;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Scheduling — the staff half of park_events. BUILD_CONTRACT.md §3, Module 4.
 *
 * ParkActivityController says what the park offers; this says when it runs. Together they
 * are "event scheduling, per-day availability" in the brief: a visitor buys a ticket for an
 * instance created here, not for the activity itself.
 *
 * The public half of this table is ParkEventController, which is read-only.
 */
class StaffEventController extends Controller
{
    /**
     * The schedule, one date at a time.
     * GET /staff/park/events → park.staff.events.index
     * Role: park_staff
     *
     * Defaults to today rather than to everything: the schedule is read a day at a time in
     * practice, and an unfiltered list grows without bound as the season goes on.
     */
    public function index(Request $request): View
    {
        $query = ParkEvent::with('activity')
            ->withCount(['tickets as sold_tickets_count' => fn ($q) => $q->where('status', '!=', 'cancelled')])
            ->orderBy('event_date')
            ->orderBy('start_time');

        $date = $request->string('date')->toString();
        $activityId = $request->integer('park_activity_id');

        if ($date !== '') {
            $query->onDate($date);
        }

        if ($activityId) {
            $query->where('park_activity_id', $activityId);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        // With no filter at all, show what is still to come rather than the whole history.
        if ($date === '' && ! $request->filled('status')) {
            $query->whereDate('event_date', '>=', now()->toDateString());
        }

        $events = $query->paginate(20)->withQueryString();
        $activities = ParkActivity::orderBy('name')->get();
        $filters = $request->only(['date', 'park_activity_id', 'status']);

        return view('park.staff.events.index', compact('events', 'activities', 'filters'));
    }

    /**
     * New event form.
     * GET /staff/park/events/create → park.staff.events.create
     */
    public function create(Request $request): View
    {
        $event = new ParkEvent();

        // Prefill from the activity when staff arrived from its page: default_capacity and
        // base_price exist on the catalogue precisely so they can be copied onto a new
        // instance and then overridden for this one.
        if ($activityId = $request->integer('park_activity_id')) {
            if ($activity = ParkActivity::find($activityId)) {
                $event->park_activity_id = $activity->id;
                $event->capacity = $activity->default_capacity;
                $event->price = $activity->base_price;
            }
        }

        return view('park.staff.events.create', [
            'event' => $event,
            'activities' => ParkActivity::active()->orderBy('name')->get(),
        ]);
    }

    /**
     * Schedule an event.
     * POST /staff/park/events
     */
    public function store(StoreParkEventRequest $request): RedirectResponse
    {
        $event = ParkEvent::create($request->validated() + ['status' => 'scheduled']);
        $event->load('activity');

        return redirect()
            ->route('park.staff.events.index', ['date' => $event->event_date->toDateString()])
            ->with('success', "{$event->activity->name} scheduled for "
                . $event->event_date->format('D j M') . '.');
    }

    /**
     * Edit a scheduled event.
     * GET /staff/park/events/{event}/edit → park.staff.events.edit
     */
    public function edit(ParkEvent $event): View
    {
        return view('park.staff.events.edit', [
            'event' => $event,
            'activities' => ParkActivity::orderBy('name')->get(),
        ]);
    }

    /**
     * Update an event.
     * PUT /staff/park/events/{event}
     *
     * Changing price does not reprice tickets already sold — tickets.unit_price is copied at
     * sale time for exactly that reason. Capacity cannot drop below seats_taken; the form
     * request refuses it, because allowing it would create the state BR-06 says cannot exist.
     */
    public function update(UpdateParkEventRequest $request, ParkEvent $event): RedirectResponse
    {
        $event->update($request->validated());

        return redirect()
            ->route('park.staff.events.index', ['date' => $event->event_date->toDateString()])
            ->with('success', 'Event updated.');
    }

    /**
     * Cancel an event.
     * POST /staff/park/events/{event}/cancel
     *
     * A status change, not a delete. Tickets already sold keep pointing at it, and the
     * validation screen refuses them with "this event was cancelled" rather than "no such
     * ticket" — which is the difference between a visitor being told what happened and
     * being told they are lying.
     */
    public function cancel(ParkEvent $event): RedirectResponse
    {
        if ($event->status === 'cancelled') {
            return back()->with('error', 'That event is already cancelled.');
        }

        $event->update(['status' => 'cancelled']);

        $sold = $event->tickets()->where('status', 'valid')->sum('quantity');

        return back()->with(
            'success',
            $sold > 0
                ? "Event cancelled. {$sold} admission(s) were already sold — those visitors need refunding."
                : 'Event cancelled.',
        );
    }

    /**
     * Delete an event.
     * DELETE /staff/park/events/{event}
     *
     * tickets.park_event_id is restrictOnDelete, so MySQL refuses once anything has been
     * sold and the sales history cannot be orphaned. Cancelling is the move in that case.
     */
    public function destroy(ParkEvent $event): RedirectResponse
    {
        try {
            $event->delete();
        } catch (QueryException) {
            return back()->with(
                'error',
                'Cannot delete this event — tickets have been sold for it. Cancel it instead.',
            );
        }

        return redirect()->route('park.staff.events.index')->with('success', 'Event deleted.');
    }
}
