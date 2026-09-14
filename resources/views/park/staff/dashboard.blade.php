@extends('layouts.app')
@section('title', 'Park Staff Dashboard')
@section('content')

<x-shared.page-header
    title="Theme Park & Beach"
    :subtitle="'Staff dashboard — ' . now()->format('l j F Y')" />

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card label="Events today" :value="$eventCount" />
    </div>
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card label="Admissions sold" :value="$admissionsSold" hint="Valid tickets, today" />
    </div>
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card label="Admitted" :value="$admissionsUsed" hint="Validated at the gate" color="secondary" />
    </div>
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card
            label="Online / gate"
            :value="$onlineToday . ' / ' . $gateToday"
            hint="Tickets by channel"
            color="success" />
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-transparent">Running today</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Start</th>
                            <th>Activity</th>
                            <th class="text-end">Taken</th>
                            <th class="text-end">Left</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($todaysEvents as $event)
                            <tr>
                                <td>{{ \Illuminate\Support\Carbon::parse($event->start_time)->format('H:i') }}</td>
                                <td class="fw-semibold">{{ $event->activity->name }}</td>
                                <td class="text-end">{{ $event->seats_taken }} / {{ $event->capacity }}</td>
                                <td class="text-end {{ $event->seatsRemaining() <= 5 ? 'text-danger fw-semibold' : '' }}">
                                    {{ $event->seatsRemaining() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <x-shared.empty-state message="Nothing is scheduled for today.">
                                        <a href="{{ route('park.staff.activities.index') }}"
                                           class="btn btn-sm btn-primary">Activity catalogue</a>
                                    </x-shared.empty-state>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-transparent">Go to</div>
            <div class="list-group list-group-flush">
                <a href="{{ route('park.staff.gate-sale') }}" class="list-group-item list-group-item-action">
                    <span class="fw-semibold">Sell at the gate</span>
                    <span class="d-block small text-body-secondary">At-entrance admissions</span>
                </a>
                <a href="{{ route('park.staff.validate') }}" class="list-group-item list-group-item-action">
                    <span class="fw-semibold">Validate tickets</span>
                    <span class="d-block small text-body-secondary">Admit or refuse at the gate</span>
                </a>
                <a href="{{ route('park.staff.capacity') }}" class="list-group-item list-group-item-action">
                    <span class="fw-semibold">Capacity monitoring</span>
                    <span class="d-block small text-body-secondary">How full each event is</span>
                </a>
                <a href="{{ route('park.staff.activities.index') }}" class="list-group-item list-group-item-action">
                    <span class="fw-semibold">Activity catalogue</span>
                    <span class="d-block small text-body-secondary">Rides, shows and beach events</span>
                </a>
            </div>
        </div>
    </div>
</div>

@endsection
