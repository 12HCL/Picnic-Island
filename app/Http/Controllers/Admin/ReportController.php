<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FerryTicket;
use App\Models\HotelBooking;
use App\Models\Payment;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Consolidated cross-module operational and financial reporting (UC-18).
     *
     * Operational figures use the date on which the service is delivered: hotel check-in,
     * ferry departure, or park event date. Revenue uses payments.paid_at, because that is
     * when money was actually recorded. Reporting is read-only.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $validated['from'] ?? now()->startOfMonth()->toDateString();
        $to = $validated['to'] ?? now()->endOfMonth()->toDateString();

        $paidPayments = Payment::query()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [
                "{$from} 00:00:00",
                "{$to} 23:59:59",
            ]);

        $breakdown = collect([
            'Hotel' => 'hotel_booking_id',
            'Ferry' => 'ferry_ticket_id',
            'Theme park & beach' => 'ticket_id',
        ])->map(function (string $foreignKey, string $module) use ($paidPayments): array {
            $query = (clone $paidPayments)->whereNotNull($foreignKey);

            return [
                'module' => $module,
                'transactions' => $query->count(),
                'revenue' => (float) (clone $query)->sum('amount'),
            ];
        })->values();

        $summary = [
            'hotel_bookings' => HotelBooking::query()->reportable($from, $to)->count(),
            'ferry_tickets' => FerryTicket::query()
                ->join('ferry_schedules', 'ferry_schedules.id', '=', 'ferry_tickets.ferry_schedule_id')
                ->whereDate('ferry_schedules.departure_date', '>=', $from)
                ->whereDate('ferry_schedules.departure_date', '<=', $to)
                ->count('ferry_tickets.id'),
            'park_admissions' => (int) Ticket::query()
                ->join('park_events', 'park_events.id', '=', 'tickets.park_event_id')
                ->whereDate('park_events.event_date', '>=', $from)
                ->whereDate('park_events.event_date', '<=', $to)
                ->where('tickets.status', '!=', 'cancelled')
                ->sum('tickets.quantity'),
            'paid_transactions' => (clone $paidPayments)->count(),
            'revenue' => (float) (clone $paidPayments)->sum('amount'),
        ];

        $recentPayments = (clone $paidPayments)
            ->with(['hotelBooking', 'ferryTicket', 'ticket'])
            ->orderByDesc('paid_at')
            ->limit(10)
            ->get();

        return view('admin.reports.index', [
            'from' => $from,
            'to' => $to,
            'summary' => $summary,
            'breakdown' => $breakdown,
            'recentPayments' => $recentPayments,
        ]);
    }
}
