<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\HotelBooking;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HotelReportController extends Controller
{
    /**
     * Booking report filtered by date range.
     * GET /staff/hotel/reports → hotel.staff.reports.index
     * Role: hotel_staff
     */
    public function index(Request $request): View
    {
        $from = $request->string('from', now()->startOfMonth()->toDateString())->toString();
        $to   = $request->string('to', now()->toDateString())->toString();

        $bookings = HotelBooking::reportable($from, $to)
            ->with(['user', 'hotel', 'rooms.roomType'])
            ->orderBy('check_in')
            ->get();

        $summary = [
            'total_bookings'   => $bookings->count(),
            'total_revenue'    => $bookings->whereIn('status', ['confirmed', 'checked_in', 'completed'])->sum('total_amount'),
            'pending_count'    => $bookings->where('status', 'pending')->count(),
            'cancelled_count'  => $bookings->where('status', 'cancelled')->count(),
        ];

        return view('hotel.staff.reports.index', compact('bookings', 'summary', 'from', 'to'));
    }
}
