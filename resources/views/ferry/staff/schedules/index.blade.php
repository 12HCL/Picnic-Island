{{--
    ferry.staff.schedules.index — the operator's timetable. Module 3, Ferry.
    Shows every sailing in every status, unlike the public list which shows only future
    sailings still on sale.
--}}
@extends('layouts.app')
@section('title', 'Ferry timetable')
@section('content')

<x-shared.page-header
    title="Ferry timetable"
    subtitle="Every sailing, with seat occupancy against vessel capacity">
    <a href="{{ route('ferry.staff.schedules.create') }}" class="btn btn-primary">+ Schedule a sailing</a>
    <a href="{{ route('ferry.dashboard') }}" class="btn btn-outline-secondary">Back to operations</a>
</x-shared.page-header>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('ferry.staff.schedules.index') }}" class="row g-3">
            <div class="col-md-4">
                <label for="status" class="form-label small text-muted">Status</label>
                <select name="status" id="status" class="form-select form-select-sm">
                    <option value="">-- Every status --</option>
                    @foreach (['scheduled', 'departed', 'cancelled'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="date" class="form-label small text-muted">Sailing date</label>
                <input type="date" name="date" id="date" class="form-control form-control-sm"
                       value="{{ request('date') }}">
            </div>
            <div class="col-md-4 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                <a href="{{ route('ferry.staff.schedules.index') }}"
                   class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th scope="col">Date</th>
                    <th scope="col">Departs</th>
                    <th scope="col">Crossing</th>
                    <th scope="col">Vessel</th>
                    <th scope="col">Occupancy</th>
                    <th scope="col">Status</th>
                    <th scope="col"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($schedules as $schedule)
                    @php
                        $capacity = $schedule->vessel->capacity;
                        $percent = $capacity > 0 ? (int) round($schedule->seats_taken / $capacity * 100) : 0;
                    @endphp
                    <tr>
                        <td>
                            {{ $schedule->departure_date->format('j M Y') }}
                            {{-- Marked rather than left for the reader to work out against a
                                 date the page never shows. --}}
                            @if ($schedule->departure_date->isToday())
                                <span class="badge text-bg-primary">Today</span>
                            @elseif ($schedule->departure_date->isPast())
                                <span class="badge text-bg-light text-body-secondary">Past</span>
                            @endif
                        </td>
                        <td>{{ \Illuminate\Support\Carbon::parse($schedule->departure_time)->format('H:i') }}</td>
                        <td>{{ $schedule->route->origin }} &rarr; {{ $schedule->route->destination }}</td>
                        <td>{{ $schedule->vessel->name }}</td>
                        <td style="min-width: 10rem;">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>{{ $schedule->seats_taken }} of {{ $capacity }}</span>
                                <span class="text-body-secondary">{{ $percent }}%</span>
                            </div>
                            {{-- BR-02 made visible: the bar fills as seats_taken approaches
                                 the vessel's capacity, and the sailing is refused at 100%. --}}
                            <div class="progress" role="progressbar" aria-valuenow="{{ $percent }}"
                                 aria-valuemin="0" aria-valuemax="100" style="height: .5rem;">
                                <div class="progress-bar {{ $percent >= 100 ? 'bg-danger' : ($percent >= 80 ? 'bg-warning' : '') }}"
                                     style="width: {{ min($percent, 100) }}%"></div>
                            </div>
                        </td>
                        <td><x-shared.status-badge :status="$schedule->status" /></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('ferry.staff.manifest', $schedule) }}"
                                   class="btn btn-outline-primary">Manifest</a>
                                <a href="{{ route('ferry.staff.schedules.edit', $schedule) }}"
                                   class="btn btn-outline-secondary">Edit</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-0">
                            <x-shared.empty-state message="No sailings match that filter." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $schedules->links() }}</div>

@endsection
