<?php

namespace App\Http\Controllers\Visitor;

use App\Http\Controllers\Controller;
use App\Models\FerryTicket;
use App\Models\HotelBooking;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyBookingsController extends Controller
{
    /**
     * Show the authenticated visitor's hotel bookings and ferry tickets.
     * Park tickets join this dashboard when Malaaz's Ticket model and tickets
     * table land; this controller must not query a table that does not exist.
     */
    public function index(Request $request): View
    {
        $userId = $request->user()->id;

        $hotelBookings = HotelBooking::query()
            ->with('hotel')
            ->where('user_id', $userId)
            ->latest()
            ->get();

        $ferryTickets = FerryTicket::query()
            ->with(['schedule.route', 'schedule.vessel'])
            ->where('user_id', $userId)
            ->latest()
            ->get();

        return view('visitor.bookings.index', [
            'hotelBookings' => $hotelBookings,
            'ferryTickets' => $ferryTickets,
        ]);
    }
}
