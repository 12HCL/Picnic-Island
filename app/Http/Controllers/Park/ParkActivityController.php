<?php

namespace App\Http\Controllers\Park;

use App\Http\Controllers\Controller;
use App\Http\Requests\Park\StoreParkActivityRequest;
use App\Http\Requests\Park\UpdateParkActivityRequest;
use App\Models\MapLocation;
use App\Models\ParkActivity;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The catalogue of things the park offers — rides, shows and beach events.
 * BUILD_CONTRACT.md §3, Module 4. MASTER_SCHEMA.md §12.
 *
 * A row here is "the Dolphin Show exists", not "the Dolphin Show runs on Tuesday".
 * The dated, ticketable instances are park_events and belong to StaffEventController.
 */
class ParkActivityController extends Controller
{
    /**
     * Activity listing with type and status filters.
     * GET /staff/park/activities → park.staff.activities.index
     * Role: park_staff
     */
    public function index(Request $request): View
    {
        $query = ParkActivity::with('mapLocation')
            ->withCount('events')
            ->orderBy('name');

        if ($request->filled('type')) {
            $query->ofType($request->string('type')->toString());
        }

        // 'active'/'inactive' rather than a boolean, so an absent filter means "both"
        // instead of silently meaning "inactive".
        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status')->toString() === 'active');
        }

        $activities = $query->paginate(20)->withQueryString();
        $filters = $request->only(['type', 'status']);

        return view('park.staff.activities.index', compact('activities', 'filters'));
    }

    /**
     * New activity form.
     * GET /staff/park/activities/create → park.staff.activities.create
     */
    public function create(): View
    {
        $activity = new ParkActivity();
        $mapLocations = MapLocation::orderBy('name')->get();

        return view('park.staff.activities.create', compact('activity', 'mapLocations'));
    }

    /**
     * Store a new activity.
     * POST /staff/park/activities
     */
    public function store(StoreParkActivityRequest $request): RedirectResponse
    {
        $activity = ParkActivity::create($request->validated());

        return redirect()->route('park.staff.activities.index')
            ->with('success', "\"{$activity->name}\" added to the catalogue.");
    }

    /**
     * Activity detail with its scheduled instances.
     * GET /staff/park/activities/{activity} → park.staff.activities.show
     */
    public function show(ParkActivity $activity): View
    {
        $activity->load(['mapLocation']);
        $events = $activity->events()
            ->orderByDesc('event_date')
            ->orderBy('start_time')
            ->paginate(15);

        return view('park.staff.activities.show', compact('activity', 'events'));
    }

    /**
     * Edit an activity.
     * GET /staff/park/activities/{activity}/edit → park.staff.activities.edit
     */
    public function edit(ParkActivity $activity): View
    {
        $mapLocations = MapLocation::orderBy('name')->get();

        return view('park.staff.activities.edit', compact('activity', 'mapLocations'));
    }

    /**
     * Update an activity.
     * PUT /staff/park/activities/{activity}
     *
     * Changing base_price does not reprice anything already scheduled: park_events.price is
     * copied at scheduling time and tickets.unit_price at sale time, so past sales and the
     * revenue reports are unaffected.
     */
    public function update(UpdateParkActivityRequest $request, ParkActivity $activity): RedirectResponse
    {
        $activity->update($request->validated());

        return redirect()->route('park.staff.activities.index')
            ->with('success', "\"{$activity->name}\" updated.");
    }

    /**
     * Delete an activity.
     * DELETE /staff/park/activities/{activity}
     *
     * park_events.park_activity_id is restrictOnDelete, so MySQL refuses while any event
     * references it and the sales history stays intact. Deactivating is the normal move —
     * is_active = false hides it from visitors without touching what it has already sold.
     */
    public function destroy(ParkActivity $activity): RedirectResponse
    {
        try {
            $activity->delete();
        } catch (QueryException) {
            return back()->with(
                'error',
                'Cannot delete this activity — it has scheduled events. Deactivate it instead.',
            );
        }

        return redirect()->route('park.staff.activities.index')
            ->with('success', 'Activity deleted.');
    }
}
