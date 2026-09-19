<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
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

         return view('home', compact('promotions', 'featuredEvents'));   
    }
}
