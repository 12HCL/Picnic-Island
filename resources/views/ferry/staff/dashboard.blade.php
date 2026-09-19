{{--
    ferry.staff.dashboard — the ferry operator's landing page. Module 3, Ferry.
    Route name ferry.dashboard, which is what DashboardController's per-role map expects.
--}}
@extends('layouts.app')
@section('title', 'Ferry operations')
@section('content')

{{-- The operator's screens talk constantly about "today" - today's sailings, a pass that is
     not for today. Nothing said what today was, which left the reader comparing dates
     against a date they had to remember. --}}
<x-shared.page-header
    title="Ferry operations"
    :subtitle="'Today is '.now()->format('l j F Y').' — sailings, boarding and counter issuance'">
    <a href="{{ route('ferry.staff.issue') }}" class="btn btn-primary">Issue a pass</a>
    <a href="{{ route('ferry.staff.validate') }}" class="btn btn-outline-secondary">Validate</a>
    <a href="{{ route('ferry.staff.reports.index') }}" class="btn btn-outline-secondary">Trip reports</a>
</x-shared.page-header>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        <x-shared.stat-card label="Sailings today" :value="$sailingsToday->count()" />
    </div>
    <div class="col-sm-6 col-lg-4">
        <x-shared.stat-card label="Seats taken today" :value="$seatsSoldToday" />
    </div>
    <div class="col-sm-6 col-lg-4">
        <x-shared.stat-card label="Upcoming sailings" :value="$upcomingSailings" />
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Today's sailings</span>
        <a href="{{ route('ferry.staff.schedules.index') }}" class="btn btn-sm btn-outline-secondary">
            Full timetable
        </a>
    </div>

    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th scope="col">Departs</th>
                    <th scope="col">Crossing</th>
                    <th scope="col">Vessel</th>
                    <th scope="col" class="text-end">Seats taken</th>
                    <th scope="col">Status</th>
                    <th scope="col"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sailingsToday as $sailing)
                    <tr>
                        <td>{{ \Illuminate\Support\Carbon::parse($sailing->departure_time)->format('H:i') }}</td>
                        <td>{{ $sailing->route->origin }} &rarr; {{ $sailing->route->destination }}</td>
                        <td>{{ $sailing->vessel->name }}</td>
                        <td class="text-end">
                            {{ $sailing->seats_taken }}
                            <span class="text-body-secondary">of {{ $sailing->vessel->capacity }}</span>
                        </td>
                        <td><x-shared.status-badge :status="$sailing->status" /></td>
                        <td class="text-end">
                            <a href="{{ route('ferry.staff.manifest', $sailing) }}"
                               class="btn btn-sm btn-outline-primary">Manifest</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-0">
                            <x-shared.empty-state message="No sailings are scheduled for today." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
