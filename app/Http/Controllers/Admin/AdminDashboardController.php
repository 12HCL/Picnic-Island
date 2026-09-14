<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FerryTicket;
use App\Models\HotelBooking;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        // created_at counts sales made today: this analytics dashboard tracks sales,
        // not arrivals. Park\StaffDashboardController deliberately uses event_date.
        $today = today();

        $stats = [
            'users_total' => User::query()->count(),
            'hotel_bookings_today' => HotelBooking::query()->whereDate('created_at', $today)->count(),
            'ferry_tickets_today' => FerryTicket::query()->whereDate('created_at', $today)->count(),
            'park_tickets_today' => Ticket::query()->whereDate('created_at', $today)->count(),
            'revenue_this_month' => Payment::query()
                ->where('status', 'paid')
                ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount'),
        ];

        $recentBookings = HotelBooking::query()
            ->with(['user', 'hotel'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'recent_bookings' => $recentBookings,
        ]);
    }
}
