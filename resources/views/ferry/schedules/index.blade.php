{{--
    ferry.schedules.index — the public sailing timetable. Module 3, Ferry.
    Public on purpose: a visitor sees what is sailing before being asked to log in.
    BR-01 is checked on the booking page, after authentication.
--}}
@extends('layouts.app')
@section('title', 'Ferry sailings')
@section('content')

<x-shared.page-header
    title="Ferry sailings"
    subtitle="Crossings between the mainland and Picnic Island" />

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('ferry.schedules.index') }}" class="row g-3">
            <div class="col-md-5">
                <label for="route" class="form-label small text-muted">Route</label>
                <select name="route" id="route" class="form-select form-select-sm">
                    <option value="">-- Every crossing --</option>
                    @foreach ($routes as $route)
                        <option value="{{ $route->id }}" @selected(request('route') == $route->id)>
                            {{ $route->origin }} to {{ $route->destination }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="date" class="form-label small text-muted">Sailing date</label>
                <input type="date" name="date" id="date" class="form-control form-control-sm"
                       value="{{ request('date') }}" min="{{ now()->toDateString() }}">
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                <a href="{{ route('ferry.schedules.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

@forelse ($schedules as $schedule)
    @if ($loop->first)
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Crossing</th>
                            <th scope="col">Departs</th>
                            <th scope="col">Vessel</th>
                            <th scope="col" class="text-end">Seats left</th>
                            <th scope="col" class="text-end">Fare</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
    @endif

                        <tr>
                            <td>
                                {{ $schedule->route->origin }} &rarr; {{ $schedule->route->destination }}
                                <div class="small text-body-secondary">
                                    {{ $schedule->route->duration_minutes }} minute crossing
                                </div>
                            </td>
                            <td>
                                {{ $schedule->departure_date->format('D j M Y') }}
                                @if ($schedule->departure_date->isToday())
                                    <span class="badge text-bg-primary">Today</span>
                                @elseif ($schedule->departure_date->isTomorrow())
                                    <span class="badge text-bg-light text-body-secondary">Tomorrow</span>
                                @endif
                                <div class="small text-body-secondary">
                                    {{ \Illuminate\Support\Carbon::parse($schedule->departure_time)->format('H:i') }}
                                </div>
                            </td>
                            <td>{{ $schedule->vessel->name }}</td>
                            <td class="text-end">
                                @if ($schedule->seatsRemaining() < 1)
                                    <span class="badge text-bg-danger">Full</span>
                                @else
                                    {{ $schedule->seatsRemaining() }}
                                    <span class="text-body-secondary">of {{ $schedule->vessel->capacity }}</span>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($schedule->route->base_fare, 2) }}</td>
                            <td class="text-end">
                                @if ($schedule->seatsRemaining() >= 1)
                                    <a href="{{ route('ferry.tickets.create', $schedule) }}"
                                       class="btn btn-sm btn-primary">Book</a>
                                @endif
                            </td>
                        </tr>

    @if ($loop->last)
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">{{ $schedules->links() }}</div>
    @endif
@empty
    <x-shared.empty-state
        message="No sailings match that filter. Try another date, or clear the filter to see every crossing." />
@endforelse

@endsection
