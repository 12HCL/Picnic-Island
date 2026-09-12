<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use Illuminate\View\View;

class HotelController extends Controller
{
    /**
     * Public listing of all hotels.
     * GET /hotels → hotel.index
     */
    public function index(): View
    {
        $hotels = Hotel::withCount('rooms')
            ->with('roomTypes')
            ->orderBy('name')
            ->get();

        return view('hotel.index', compact('hotels'));
    }

    /**
     * Single hotel detail with its room types.
     * GET /hotels/{hotel} → hotel.show
     */
    public function show(Hotel $hotel): View
    {
        $hotel->load(['roomTypes', 'rooms.roomType']);

        return view('hotel.show', compact('hotel'));
    }
}
