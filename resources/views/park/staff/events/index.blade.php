@extends('layouts.app')
@section('title', 'Event Schedule')
@section('content')

<x-shared.page-header
    title="Schedule"
    subtitle="When each activity runs. This is what visitors buy tickets for.">
    <a href="{{ route('park.staff.events.create') }}" class="btn btn-primary btn-sm">+ Schedule event</a>
    <a href="{{ route('park.dashboard') }}" class="btn btn-outline-secondary btn-sm">&larr; Dashboard</a>
</x-shared.page-header>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('park.staff.events.index') }}" class="row g-3">
            <div class="col-md-3">
                <label for="date" class="form-label small text-muted">Date</label>
                <input type="date" name="date" id="date" class="form-control form-control-sm"
                       value="{{ $filters['date'] ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="park_activity_id" class="form-label small text-muted">Activity</label>
                <select name="park_activity_id" id="park_activity_id" class="form-select form-select-sm">
                    <option value="">-- All activities --</option>
                    @foreach ($activities as $activity)
                        <option value="{{ $activity->id }}"
                            {{ (int) ($filters['park_activity_id'] ?? 0) === $activity->id ? 'selected' : '' }}>
                            {{ $activity->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label small text-muted">Status</label>
                <select name="status" id="status" class="form-select form-select-sm">
                    <option value="">-- Upcoming --</option>
                    @foreach (['scheduled' => 'Scheduled', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                <a href="{{ route('park.staff.events.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Start</th>
                    <th>Activity</th>
                    <th class="text-end">Capacity</th>
                    <th class="text-end">Taken</th>
                    <th class="text-end">Tickets</th>
                    <th class="text-end">Price</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($events as $event)
                    <tr>
                        <td>{{ $event->event_date->format('D j M Y') }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($event->start_time)->format('H:i') }}</td>
                        <td class="fw-semibold">{{ $event->activity->name }}</td>
                        <td class="text-end">{{ $event->capacity }}</td>
                        <td class="text-end">{{ $event->seats_taken }}</td>
                        <td class="text-end">{{ $event->sold_tickets_count }}</td>
                        <td class="text-end">MVR {{ number_format((float) $event->price, 2) }}</td>
                        <td><x-shared.status-badge :status="$event->status" /></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('park.staff.events.edit', $event) }}"
                                   class="btn btn-outline-secondary">Edit</a>

                                @if ($event->status === 'scheduled')
                                    <form method="POST" action="{{ route('park.staff.events.cancel', $event) }}"
                                          onsubmit="return confirm('Cancel this event? Tickets already sold will need refunding.');">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-warning">Cancel</button>
                                    </form>
                                @endif

                                @if ($event->sold_tickets_count === 0)
                                    <form method="POST" action="{{ route('park.staff.events.destroy', $event) }}"
                                          onsubmit="return confirm('Delete this event?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <x-shared.empty-state message="Nothing scheduled. Visitors have nothing to buy until something is.">
                                <a href="{{ route('park.staff.events.create') }}" class="btn btn-sm btn-primary">
                                    Schedule the first event
                                </a>
                            </x-shared.empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $events->links() }}</div>

@endsection
