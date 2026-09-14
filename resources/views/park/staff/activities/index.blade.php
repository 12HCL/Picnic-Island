@extends('layouts.app')
@section('title', 'Park Activities')
@section('content')

<x-shared.page-header
    title="Activity catalogue"
    subtitle="Rides, shows and beach events the park offers. Scheduling them is done under Events.">
    <a href="{{ route('park.staff.activities.create') }}" class="btn btn-primary btn-sm">+ Add activity</a>
</x-shared.page-header>

{{-- Filters --}}
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('park.staff.activities.index') }}" class="row g-3">
            <div class="col-md-4">
                <label for="type" class="form-label small text-muted">Type</label>
                <select name="type" id="type" class="form-select form-select-sm">
                    <option value="">-- All types --</option>
                    @foreach (['ride' => 'Ride', 'show' => 'Show', 'beach_event' => 'Beach event'] as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['type'] ?? '') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label small text-muted">Status</label>
                <select name="status" id="status" class="form-select form-select-sm">
                    <option value="">-- All --</option>
                    <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                <a href="{{ route('park.staff.activities.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Location</th>
                    <th class="text-end">Default capacity</th>
                    <th class="text-end">Base price</th>
                    <th class="text-end">Scheduled</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activities as $activity)
                    <tr>
                        <td>
                            <a href="{{ route('park.staff.activities.show', $activity) }}"
                               class="text-decoration-none fw-semibold">
                                {{ $activity->name }}
                            </a>
                        </td>
                        <td class="text-capitalize">{{ str_replace('_', ' ', $activity->type) }}</td>
                        <td class="small text-body-secondary">
                            {{ $activity->mapLocation->name ?? '—' }}
                        </td>
                        <td class="text-end">{{ $activity->default_capacity }}</td>
                        <td class="text-end">MVR {{ number_format((float) $activity->base_price, 2) }}</td>
                        <td class="text-end">{{ $activity->events_count }}</td>
                        <td>
                            <span class="badge text-bg-{{ $activity->is_active ? 'success' : 'secondary' }}">
                                {{ $activity->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('park.staff.activities.edit', $activity) }}"
                                   class="btn btn-outline-secondary">Edit</a>
                                <form method="POST"
                                      action="{{ route('park.staff.activities.destroy', $activity) }}"
                                      onsubmit="return confirm('Delete {{ $activity->name }}? Deactivating is usually safer.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <x-shared.empty-state message="No activities in the catalogue yet.">
                                <a href="{{ route('park.staff.activities.create') }}" class="btn btn-sm btn-primary">
                                    Add the first one
                                </a>
                            </x-shared.empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $activities->links() }}</div>

@endsection
