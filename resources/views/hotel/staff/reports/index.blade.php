@extends('layouts.app')
@section('title', 'Hotel Booking & Revenue Reports')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Hotel Reports</h1>
        <p class="text-muted small mb-0">Financial and occupancy analytics for room bookings</p>
    </div>
    <a href="{{ route('hotel.dashboard') }}" class="btn btn-outline-secondary btn-sm">&larr; Dashboard</a>
</div>

{{-- Date filter --}}
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('hotel.staff.reports.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="from" class="form-label small text-muted">From Date</label>
                <input type="date" name="from" id="from" value="{{ $from }}" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-4">
                <label for="to" class="form-label small text-muted">To Date</label>
                <input type="date" name="to" id="to" value="{{ $to }}" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Generate Report</button>
                <a href="{{ route('hotel.staff.reports.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- Stat summary cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card
            label="Total Bookings"
            :value="$summary['total_bookings']"
            hint="In selected period"
            color="primary" />
    </div>
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card
            label="Total Revenue"
            :value="'MVR ' . number_format($summary['total_revenue'], 2)"
            hint="Confirmed & completed"
            color="success" />
    </div>
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card
            label="Pending Bookings"
            :value="$summary['pending_count']"
            hint="Unconfirmed reservations"
            color="warning" />
    </div>
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card
            label="Cancelled Bookings"
            :value="$summary['cancelled_count']"
            hint="Cancelled by guest or staff"
            color="danger" />
    </div>
</div>

{{-- Bookings Table --}}
<div class="card shadow-sm">
    <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0">Detailed Booking Log ({{ \Carbon\Carbon::parse($from)->format('d M Y') }} &ndash; {{ \Carbon\Carbon::parse($to)->format('d M Y') }})</h2>
        <span class="badge bg-secondary">{{ $bookings->count() }} records</span>
    </div>
    <div class="card-body p-0">
        @if ($bookings->isEmpty())
            <div class="p-4 text-center text-muted">
                No hotel bookings recorded within this date range.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>Reference</th>
                            <th>Guest</th>
                            <th>Hotel</th>
                            <th>Stay Period</th>
                            <th>Rooms</th>
                            <th class="text-end">Total Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bookings as $booking)
                            <tr>
                                <td>
                                    <a href="{{ route('hotel.bookings.show', $booking) }}" class="fw-bold text-decoration-none">
                                        {{ $booking->reference }}
                                    </a>
                                </td>
                                <td>
                                    <div>{{ $booking->user->name }}</div>
                                    <small class="text-muted">{{ $booking->user->email }}</small>
                                </td>
                                <td>{{ $booking->hotel->name }}</td>
                                <td class="small">
                                    {{ $booking->check_in->format('d M Y') }} &ndash; {{ $booking->check_out->format('d M Y') }}
                                </td>
                                <td class="small">
                                    @foreach ($booking->rooms as $rm)
                                        <span class="badge bg-light text-dark border">#{{ $rm->room_number }} ({{ $rm->roomType->name }})</span>
                                    @endforeach
                                </td>
                                <td class="text-end fw-semibold">
                                    MVR {{ number_format($booking->total_amount, 2) }}
                                </td>
                                <td>
                                    <x-shared.status-badge :status="$booking->status" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="5" class="text-end">Total Period Revenue:</th>
                            <th class="text-end text-success fs-6">MVR {{ number_format($summary['total_revenue'], 2) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>

@endsection
