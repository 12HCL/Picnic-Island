<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Models\MapLocation;
use Illuminate\View\View;

/**
 * Module 5 - Content, Map & Reporting. Owner: Ahmed Safhaan.
 * The public island map. One static image with clickable markers positioned by
 * percentage, so it works at any screen size and needs no maps API — the Dean
 * explicitly excluded one.
 */
class MapController extends Controller
{
    /**
     * GET /map  →  view 'map'
     *
     * The build contract says this view receives exactly two variables:
     *   locations => Collection of MapLocation
     *   mapImage  => string
     */
    public function index(): View
    {
        // MapLocation::visible() is the scope defined on the model. It adds
        // "where is_visible = 1", so a location can be hidden from visitors
        // without being deleted (UC-08 business rule).
        $locations = MapLocation::visible()
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return view('map', [
            'locations' => $locations,
            'mapImage' => 'img/island-map-v2.webp',
        ]);
    }
}
