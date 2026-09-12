@extends('layouts.app')
@section('title', 'Hotel Bookings Management')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Hotel Bookings</h1>
        <p class="text-muted small mb-0">Manage customer reservations, confirmations, and check-ins</p>
    </div>
    <a href="{{ route('hotel.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        &larr; Staff Dashboard
    </a>
</div>

{{-- Filters --}}
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('hotel.staff.bookings.index') }}" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label small text-muted">Search Reference or Guest</label>
                <input type="text" name="search" id="search" class="form-control form-control-sm"
                       placeholder="e.g. PIB-HB-000001 or John" value="{{ $filters['search'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label small text-muted">Status</label>
                <select name="status" id="status" class="form-select form-select-sm">
                    <option value="">-- All Statuses --</option>
                    @foreach (['pending', 'confirmed', 'checked_in', 'completed', 'cancelled'] as $st)
                        <option value="{{ $st }}" {{ ($filters['status'] ?? '') === $st ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $st)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="date" class="form-label small text-muted">Check-in Date</label>
                <input type="date" name="date" id="date" class="form-control form-control-sm"
                       value="{{ $filters['date'] ?? '' }}">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                <a href="{{ route('hotel.staff.bookings.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

{{-- Bookings Table --}}
<div class="card shadow-sm">
    <div class="card-body p-0">
        @if ($bookings->isEmpty())
            <div class="p-4 text-center text-muted">
                No hotel bookings match the selected filters.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>Reference</th>
                            <th>Guest</th>
                            <th>Hotel</th>
                            <th>Stay Dates</th>
                            <th class="text-center">Guests</th>
                            <th class="text-end">Total Amount</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bookings as $booking)
                            <tr>
                                <td><strong>{{ $booking->reference }}</strong></td>
                                <td>
                                    <div>{{ $booking->user->name }}</div>
                                    <small class="text-muted">{{ $booking->user->email }}</small>
                                </td>
                                <td>{{ $booking->hotel->name }}</td>
                                <td class="small">
                                    {{ $booking->check_in->format('d M Y') }} &ndash; {{ $booking->check_out->format('d M Y') }}
                                </td>
                                <td class="text-center">{{ $booking->guests }}</td>
                                <td class="text-end fw-semibold">MVR {{ number_format($booking->total_amount, 2) }}</td>
                                <td>
                                    <x-shared.status-badge :status="$booking->status" />
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('hotel.bookings.show', $booking) }}" class="btn btn-outline-primary" title="View details">
                                            View
                                        </a>

                                        @if ($booking->status === 'pending')
                                            <form action="{{ route('hotel.staff.bookings.confirm', $booking) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success" title="Confirm booking">
                                                    Confirm
                                                </button>
                                            </form>
                                        @elseif ($booking->status === 'confirmed')
                                            <form action="{{ route('hotel.staff.bookings.check-in', $booking) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-primary" title="Check in guest">
                                                    Check-in
                                                </button>
                                            </form>
                                        @elseif ($booking->status === 'checked_in')
                                            <form action="{{ route('hotel.staff.bookings.check-out', $booking) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-info" title="Check out guest">
                                                    Check-out
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($bookings->hasPages())
                <div class="card-footer bg-transparent py-3">
                    {{ $bookings->links() }}
                </div>
            @endif
        @endif
    </div>
</div>

@endsection
