@extends('layouts.app')

@section('title', 'System reports')

@section('content')
    <x-shared.page-header
        title="System reports"
        subtitle="Consolidated operational activity and paid revenue across hotel, ferry, and park services."
    >
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">&larr; Dashboard</a>
    </x-shared.page-header>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="from" class="form-label">From</label>
                    <input type="date" name="from" id="from" class="form-control" value="{{ $from }}">
                </div>
                <div class="col-md-4">
                    <label for="to" class="form-label">To</label>
                    <input type="date" name="to" id="to" class="form-control" value="{{ $to }}">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">Run report</button>
                    <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
            <p class="small text-body-secondary mb-0 mt-3">
                Activity is dated by check-in, ferry departure, or park event. Revenue is dated by payment.
            </p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl">
            <x-shared.stat-card label="Hotel arrivals" :value="$summary['hotel_bookings']" />
        </div>
        <div class="col-sm-6 col-xl">
            <x-shared.stat-card label="Ferry passengers" :value="$summary['ferry_tickets']" />
        </div>
        <div class="col-sm-6 col-xl">
            <x-shared.stat-card label="Park admissions" :value="$summary['park_admissions']" />
        </div>
        <div class="col-sm-6 col-xl">
            <x-shared.stat-card label="Paid transactions" :value="$summary['paid_transactions']" />
        </div>
        <div class="col-sm-6 col-xl">
            <x-shared.stat-card label="Revenue" :value="'MVR ' . number_format($summary['revenue'], 2)" color="success" />
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h2 class="h5 mb-0">Revenue by module</h2>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Module</th>
                                <th class="text-end">Payments</th>
                                <th class="text-end">Revenue (MVR)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($breakdown as $row)
                                <tr>
                                    <td>{{ $row['module'] }}</td>
                                    <td class="text-end">{{ $row['transactions'] }}</td>
                                    <td class="text-end">{{ number_format($row['revenue'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h2 class="h5 mb-0">Recent paid transactions</h2>
                </div>
                @if ($recentPayments->isEmpty())
                    <x-shared.empty-state message="No paid transactions in this date range." />
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Reference</th>
                                    <th>Module</th>
                                    <th>Method</th>
                                    <th>Paid</th>
                                    <th class="text-end">Amount (MVR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentPayments as $payment)
                                    <tr>
                                        <td class="fw-semibold">{{ $payment->reference }}</td>
                                        <td>
                                            @if ($payment->hotel_booking_id)
                                                Hotel
                                            @elseif ($payment->ferry_ticket_id)
                                                Ferry
                                            @else
                                                Theme park &amp; beach
                                            @endif
                                        </td>
                                        <td class="text-capitalize">{{ $payment->method }}</td>
                                        <td>{{ $payment->paid_at->format('d M Y H:i') }}</td>
                                        <td class="text-end">{{ number_format((float) $payment->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
