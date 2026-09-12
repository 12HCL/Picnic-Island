<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\HotelBooking;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StaffDashboardController extends Controller
{
    /**
     * Hotel staff home — key numbers for today.
     * GET /staff/hotel → hotel.staff.dashboard
     * Route name: hotel.dashboard  (matches DashboardController's redirect map)
     * Role: hotel_staff
     */
    public function index(): View
    {
        $today = now()->toDateString();

        $stats = [
            'pending_count'    => HotelBooking::where('status', 'pending')->count(),
            'checkins_today'   => HotelBooking::whereDate('check_in', $today)
                                    ->whereIn('status', ['confirmed', 'checked_in'])
                                    ->count(),
            'checkouts_today'  => HotelBooking::whereDate('check_out', $today)
                                    ->whereIn('status', ['confirmed', 'checked_in'])
                                    ->count(),
            'revenue_month'    => HotelBooking::whereMonth('created_at', now()->month)
                                    ->whereYear('created_at', now()->year)
                                    ->whereIn('status', ['confirmed', 'checked_in', 'completed'])
                                    ->sum('total_amount'),
        ];

        $recentBookings = HotelBooking::with(['user', 'hotel'])
            ->latest()
            ->limit(10)
            ->get();

        return view('hotel.staff.dashboard', compact('stats', 'recentBookings'));
    }
}
