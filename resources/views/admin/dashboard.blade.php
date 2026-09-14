@extends('layouts.app')

@section('title', 'Admin dashboard')

@section('content')
    <x-shared.page-header
        title="Admin dashboard"
        subtitle="A current overview of accounts, bookings, ticket sales, and revenue."
    >
        <a href="{{ route('admin.users.index') }}" class="btn btn-primary">Manage users</a>
    </x-shared.page-header>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl">
            <x-shared.stat-card label="Users" :value="$stats['users_total']" />
        </div>
        <div class="col-sm-6 col-xl">
            <x-shared.stat-card label="Hotel bookings today" :value="$stats['hotel_bookings_today']" />
        </div>
        <div class="col-sm-6 col-xl">
            <x-shared.stat-card label="Ferry tickets today" :value="$stats['ferry_tickets_today']" />
        </div>
        <div class="col-sm-6 col-xl">
            <x-shared.stat-card label="Park tickets today" :value="$stats['park_tickets_today']" />
        </div>
        <div class="col-sm-6 col-xl">
            <x-shared.stat-card
                label="Revenue this month"
                :value="'MVR ' . number_format($stats['revenue_this_month'], 2)"
                hint="Paid payments only"
                color="success"
            />
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h2 class="h5 mb-0">Recent hotel bookings</h2>
        </div>

        @if ($recent_bookings->isEmpty())
            <x-shared.empty-state message="No hotel bookings have been made yet." />
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Reference</th>
                            <th scope="col">Visitor</th>
                            <th scope="col">Hotel</th>
                            <th scope="col">Stay</th>
                            <th scope="col" class="text-end">Total (MVR)</th>
                            <th scope="col">Status</th>
                            <th scope="col">Booked</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recent_bookings as $booking)
                            <tr>
                                <td class="fw-semibold">{{ $booking->reference }}</td>
                                <td>{{ $booking->user->name }}</td>
                                <td>{{ $booking->hotel->name }}</td>
                                <td>
                                    {{ $booking->check_in->format('d M Y') }}
                                    &ndash;
                                    {{ $booking->check_out->format('d M Y') }}
                                </td>
                                <td class="text-end">{{ number_format($booking->total_amount, 2) }}</td>
                                <td>
                                    <x-shared.status-badge :status="$booking->status" />
                                </td>
                                <td>{{ $booking->created_at->format('d M Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
