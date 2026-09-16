<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\StoreMapLocationRequest;
use App\Http\Requests\Content\UpdateMapLocationRequest;
use App\Models\MapLocation;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin management of the markers on the static island map.
 * BUILD_CONTRACT.md §3, Module 5. MASTER_SCHEMA.md §16. UC-18 steps 1–4.
 *
 * A row here is one clickable point on public/img/island-map.svg. pos_x and pos_y are
 * PERCENTAGES of the image (0–100), not pixels and not latitude/longitude — the Dean
 * excluded a maps API, and percentages keep the markers correct at any rendered size.
 *
 * The public read side of this table is Content\MapController. This is the write side.
 */
class MapLocationController extends Controller
{
    /**
     * Location listing with category and visibility filters.
     * GET /admin/map-locations → content.map-locations.index
     * Role: admin
     */
    public function index(Request $request): View
    {
        $query = MapLocation::withCount('activities')
            ->orderBy('category')
            ->orderBy('name');

        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }

        // 'visible'/'hidden' rather than a boolean, so an absent filter means "both"
        // instead of silently meaning "hidden".
        if ($request->filled('visibility')) {
            $query->where('is_visible', $request->string('visibility')->toString() === 'visible');
        }

        $locations = $query->paginate(20)->withQueryString();
        $filters = $request->only(['category', 'visibility']);

        return view('content.map-locations.index', compact('locations', 'filters'));
    }

    /**
     * New location form.
     * GET /admin/map-locations/create → content.map-locations.create
     */
    public function create(): View
    {
        $location = new MapLocation();

        return view('content.map-locations.create', compact('location'));
    }

    /**
     * Store a new location.
     * POST /admin/map-locations
     */
    public function store(StoreMapLocationRequest $request): RedirectResponse
    {
        $location = MapLocation::create($request->validated());

        return redirect()->route('content.map-locations.index')
            ->with('success', "\"{$location->name}\" added to the island map.");
    }

    /**
     * Location detail, with the park activities that point at it.
     * GET /admin/map-locations/{mapLocation} → content.map-locations.show
     */
    public function show(MapLocation $mapLocation): View
    {
        $mapLocation->load('activities');

        return view('content.map-locations.show', ['location' => $mapLocation]);
    }

    /**
     * Edit a location.
     * GET /admin/map-locations/{mapLocation}/edit → content.map-locations.edit
     */
    public function edit(MapLocation $mapLocation): View
    {
        return view('content.map-locations.edit', ['location' => $mapLocation]);
    }

    /**
     * Update a location.
     * PUT /admin/map-locations/{mapLocation}
     *
     * Moving a marker changes only where it is drawn. Nothing copies pos_x/pos_y, so
     * no other module's data is affected by a reposition.
     */
    public function update(UpdateMapLocationRequest $request, MapLocation $mapLocation): RedirectResponse
    {
        $mapLocation->update($request->validated());

        return redirect()->route('content.map-locations.index')
            ->with('success', "\"{$mapLocation->name}\" updated.");
    }

    /**
     * Delete a location.
     * DELETE /admin/map-locations/{mapLocation}
     *
     * park_activities.map_location_id references this table (BUILD_CONTRACT.md §6 seam 3).
     * The delete rule is Module 4's to set, so this catches the database's refusal rather
     * than assuming it will allow the delete. Hiding is the normal move anyway —
     * is_visible = false takes the marker off the public map without breaking a reference.
     */
    public function destroy(MapLocation $mapLocation): RedirectResponse
    {
        try {
            $mapLocation->delete();
        } catch (QueryException) {
            return back()->with(
                'error',
                'Cannot delete this location — park activities are placed here. Hide it instead.',
            );
        }

        return redirect()->route('content.map-locations.index')
            ->with('success', 'Location deleted.');
    }
}
