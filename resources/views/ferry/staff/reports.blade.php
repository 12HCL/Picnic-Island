{{--
    ferry.staff.reports.index — trip reports. Module 3, Ferry.

    Every sailing in the date range with passes sold, boarded, cancelled, revenue and
    occupancy. Cancelled passes are not counted as sold, because cancelling returns the seat.
--}}
@extends('layouts.app')
@section('title', 'Trip reports')
@section('content')

<x-shared.page-header
    title="Trip reports"
    :subtitle="'Sailings from '.\Illuminate\Support\Carbon::parse($from)->format('j M Y').' to '.\Illuminate\Support\Carbon::parse($to)->format('j M Y')">
    <a href="{{ route('ferry.dashboard') }}" class="btn btn-outline-secondary">Ferry operations</a>
</x-shared.page-header>

<form method="GET" action="{{ route('ferry.staff.reports.index') }}" class="card card-body shadow-sm mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-sm-4">
            <label for="from" class="form-label small text-body-secondary">From</label>
            <input type="date" name="from" id="from" value="{{ $from }}"
                   class="form-control @error('from') is-invalid @enderror">
            @error('from') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-sm-4">
            <label for="to" class="form-label small text-body-secondary">To</label>
            <input type="date" name="to" id="to" value="{{ $to }}"
                   class="form-control @error('to') is-invalid @enderror">
            @error('to') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-sm-4">
            <button type="submit" class="btn btn-primary w-100">Show report</button>
        </div>
    </div>
</form>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card label="Sailings" :value="$summary['sailings']" />
    </div>
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card label="Passes sold" :value="$summary['sold']"
            :hint="$summary['boarded'].' boarded'" />
    </div>
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card label="Revenue" :value="'MVR '.number_format($summary['revenue'], 2)"
            hint="Cancelled passes excluded" color="success" />
    </div>
    <div class="col-sm-6 col-lg-3">
        <x-shared.stat-card label="Average occupancy" :value="$summary['occupancy'].'%'"
            hint="Cancelled sailings excluded" color="secondary" />
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th scope="col">Sailing</th>
                    <th scope="col">Route</th>
                    <th scope="col">Vessel</th>
                    <th scope="col" class="text-end">Sold</th>
                    <th scope="col" class="text-end">Boarded</th>
                    <th scope="col" class="text-end">Cancelled</th>
                    <th scope="col" class="text-end">Revenue</th>
                    <th scope="col">Occupancy</th>
                    <th scope="col">Status</th>
                    <th scope="col"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sailings as $sailing)
                    @php
                        $percent = $sailing->vessel->capacity > 0
                            ? round($sailing->sold_count / $sailing->vessel->capacity * 100)
                            : 0;
                    @endphp
                    <tr @class(['table-light text-body-secondary' => $sailing->status === 'cancelled'])>
                        <td>
                            {{ $sailing->departure_date->format('D j M') }}
                            <div class="small text-body-secondary">
                                {{ \Illuminate\Support\Carbon::parse($sailing->departure_time)->format('H:i') }}
                            </div>
                        </td>
                        <td>{{ $sailing->route->origin }} to {{ $sailing->route->destination }}</td>
                        <td>{{ $sailing->vessel->name }}</td>
                        <td class="text-end">{{ $sailing->sold_count }}</td>
                        <td class="text-end">{{ $sailing->boarded_count }}</td>
                        <td class="text-end">{{ $sailing->cancelled_count }}</td>
                        <td class="text-end">MVR {{ number_format((float) $sailing->revenue, 2) }}</td>
                        <td style="min-width: 8rem">
                            <div class="progress" role="progressbar" aria-label="Occupancy"
                                 aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar" style="width: {{ $percent }}%"></div>
                            </div>
                            <div class="small text-body-secondary">
                                {{ $sailing->sold_count }} of {{ $sailing->vessel->capacity }} ({{ $percent }}%)
                            </div>
                        </td>
                        <td><x-shared.status-badge :status="$sailing->status" /></td>
                        <td>
                            <a href="{{ route('ferry.staff.manifest', $sailing) }}"
                               class="btn btn-sm btn-outline-secondary">Manifest</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="p-0">
                            <x-shared.empty-state message="No sailings in this date range." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
