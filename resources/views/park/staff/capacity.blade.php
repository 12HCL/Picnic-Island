@extends('layouts.app')
@section('title', 'Capacity Monitoring')
@section('content')

<x-shared.page-header
    title="Capacity monitoring"
    subtitle="How full each event is. BR-06 is enforced at the point of sale, not here.">
    <a href="{{ route('park.dashboard') }}" class="btn btn-outline-secondary btn-sm">&larr; Dashboard</a>
</x-shared.page-header>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('park.staff.capacity') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="date" class="form-label small text-muted">Date</label>
                <input type="date" name="date" id="date" class="form-control form-control-sm" value="{{ $date }}">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary btn-sm">Show</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card label="Scheduled events" :value="$events->where('status', 'scheduled')->count()" />
    </div>
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card
            label="Admissions taken"
            :value="$totalTaken . ' / ' . $totalCapacity"
            hint="Across scheduled events" />
    </div>
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card
            label="Nearly full"
            :value="$nearlyFull"
            hint="At 90% or above"
            color="warning" />
    </div>
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card label="Sold out" :value="$soldOut" color="danger" />
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Start</th>
                    <th>Activity</th>
                    <th>Type</th>
                    <th class="text-end">Capacity</th>
                    <th class="text-end">Taken</th>
                    <th class="text-end">Left</th>
                    <th style="min-width: 160px;">Full</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($events as $event)
                    @php
                        $bar = $event->percent_full >= 100 ? 'danger'
                            : ($event->percent_full >= 90 ? 'warning' : 'success');
                    @endphp
                    <tr>
                        <td>{{ \Illuminate\Support\Carbon::parse($event->start_time)->format('H:i') }}</td>
                        <td class="fw-semibold">{{ $event->activity->name }}</td>
                        <td class="text-capitalize small text-body-secondary">
                            {{ str_replace('_', ' ', $event->activity->type) }}
                        </td>
                        <td class="text-end">{{ $event->capacity }}</td>
                        <td class="text-end">{{ $event->seats_taken }}</td>
                        <td class="text-end {{ $event->seats_remaining <= 5 ? 'text-danger fw-semibold' : '' }}">
                            {{ $event->seats_remaining }}
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height: 8px;"
                                     role="progressbar"
                                     aria-valuenow="{{ $event->percent_full }}"
                                     aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar bg-{{ $bar }}"
                                         style="width: {{ min(100, $event->percent_full) }}%"></div>
                                </div>
                                <span class="small text-body-secondary" style="min-width: 3rem;">
                                    {{ $event->percent_full }}%
                                </span>
                            </div>
                        </td>
                        <td><x-shared.status-badge :status="$event->status" /></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <x-shared.empty-state message="Nothing is scheduled for that date.">
                                <a href="{{ route('park.staff.activities.index') }}" class="btn btn-sm btn-primary">
                                    Activity catalogue
                                </a>
                            </x-shared.empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
