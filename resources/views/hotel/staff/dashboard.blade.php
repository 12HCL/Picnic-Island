@extends('layouts.app')
@section('title', 'Hotel Staff Dashboard')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Hotel Staff Dashboard</h1>
        <p class="text-muted small mb-0">Overview of today's hotel operations and room activity</p>
    </div>
    <div class="btn-group">
        <a href="{{ route('hotel.staff.bookings.index') }}" class="btn btn-outline-primary btn-sm">All Bookings</a>
        <a href="{{ route('hotel.staff.rooms.index') }}" class="btn btn-outline-secondary btn-sm">Manage Rooms</a>
        <a href="{{ route('hotel.staff.reports.index') }}" class="btn btn-outline-info btn-sm">Reports</a>
    </div>
</div>

{{-- Stat cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card
            label="Pending Bookings"
            :value="$stats['pending_count']"
            hint="Awaiting confirmation"
            color="warning" />
    </div>
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card
            label="Check-ins Today"
            :value="$stats['checkins_today']"
            hint="Scheduled arrivals"
            color="primary" />
    </div>
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card
            label="Check-outs Today"
            :value="$stats['checkouts_today']"
            hint="Scheduled departures"
            color="secondary" />
    </div>
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card
            label="Monthly Revenue"
            :value="'MVR ' . number_format($stats['revenue_month'], 2)"
            hint="Confirmed & completed"
            color="success" />
    </div>
</div>

{{-- Recent bookings table --}}
<div class="card shadow-sm">
    <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0">Recent Hotel Bookings</h2>
        <a href="{{ route('hotel.staff.bookings.index') }}" class="btn btn-sm btn-link p-0 text-decoration-none">View all &rarr;</a>
    </div>
    <div class="card-body p-0">
        @if ($recentBookings->isEmpty())
            <div class="p-4 text-center text-muted">No bookings recorded yet.</div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>Reference</th>
                            <th>Guest</th>
                            <th>Hotel</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th class="text-end">Total (MVR)</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentBookings as $booking)
                            <tr>
                                <td><strong>{{ $booking->reference }}</strong></td>
                                <td>
                                    <div>{{ $booking->user->name }}</div>
                                    <small class="text-muted">{{ $booking->user->email }}</small>
                                </td>
                                <td>{{ $booking->hotel->name }}</td>
                                <td>{{ $booking->check_in->format('d M Y') }}</td>
                                <td>{{ $booking->check_out->format('d M Y') }}</td>
                                <td class="text-end fw-semibold">{{ number_format($booking->total_amount, 2) }}</td>
                                <td>
                                    <x-shared.status-badge :status="$booking->status" />
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('hotel.bookings.show', $booking) }}" class="btn btn-sm btn-outline-primary">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@endsection
