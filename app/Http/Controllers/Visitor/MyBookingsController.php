<?php

namespace App\Http\Controllers\Visitor;

use App\Http\Controllers\Controller;
use App\Models\FerryTicket;
use App\Models\HotelBooking;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyBookingsController extends Controller
{
    /**
     * Show the authenticated visitor's hotel bookings, ferry tickets and park tickets.
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

        // tickets.user_id is nullable: a walk-up gate sale has a ticket and no account
        // behind it (MASTER_SCHEMA.md §14). The equality filter below is deliberate and
        // sufficient - NULL never equals an integer in SQL, so gate sales are excluded on
        // their own. Do not "fix" this with orWhereNull(): that would show every anonymous
        // sale in the system to every logged-in visitor.
        $parkTickets = Ticket::query()
            ->with(['event.activity'])
            ->where('user_id', $userId)
            ->latest()
            ->get();

        return view('visitor.bookings.index', [
            'hotelBookings' => $hotelBookings,
            'ferryTickets' => $ferryTickets,
            'parkTickets' => $parkTickets,
        ]);
    }
}
