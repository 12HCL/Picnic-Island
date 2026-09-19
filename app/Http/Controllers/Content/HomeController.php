<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use App\Models\FerryRoute;
use App\Models\Hotel;
use App\Models\ParkEvent;
use App\Models\Promotion;

/**
 * Module 5: Content, Map & Reporting. Owner: Ahmed Safhaan.
 */

class HomeController extends Controller
{
    
    public function index(): View
    {
        $promotions = Promotion::live()
            ->orderByDesc('starts_on')
            ->get();
        
        $featuredEvents = ParkEvent::bookable()
            ->with('activity')
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->limit(6)
            ->get();

        // Featured stays. withMin gives the "from" price without loading every room type.
        $hotels = Hotel::query()
            ->withMin('roomTypes', 'base_price')
            ->orderByDesc('star_rating')
            ->limit(4)
            ->get();

        // For the crossing search strip, which submits to the real ferry timetable.
        $ferryRoutes = FerryRoute::orderBy('origin')->get();

         return view('home', compact('promotions', 'featuredEvents', 'hotels', 'ferryRoutes'));
    }
}
