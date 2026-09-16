@extends('layouts.app')
@section('title', 'Map Locations')
@section('content')

<x-shared.page-header
    title="Island map locations"
    subtitle="The clickable markers on the public island map. UC-18.">
    <a href="{{ route('content.map') }}" class="btn btn-outline-secondary btn-sm">View public map</a>
    <a href="{{ route('content.map-locations.create') }}" class="btn btn-primary btn-sm">+ Add location</a>
</x-shared.page-header>

{{-- Filters --}}
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('content.map-locations.index') }}" class="row g-3">
            <div class="col-md-4">
                <label for="category" class="form-label small text-muted">Category</label>
                <select name="category" id="category" class="form-select form-select-sm">
                    <option value="">-- All categories --</option>
                    @foreach (['hotel' => 'Hotel', 'jetty' => 'Jetty', 'attraction' => 'Attraction', 'beach' => 'Beach', 'facility' => 'Facility'] as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['category'] ?? '') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="visibility" class="form-label small text-muted">Visibility</label>
                <select name="visibility" id="visibility" class="form-select form-select-sm">
                    <option value="">-- All --</option>
                    <option value="visible" {{ ($filters['visibility'] ?? '') === 'visible' ? 'selected' : '' }}>Visible</option>
                    <option value="hidden" {{ ($filters['visibility'] ?? '') === 'hidden' ? 'selected' : '' }}>Hidden</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                <a href="{{ route('content.map-locations.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
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
                    <th>Category</th>
                    <th class="text-end">Position (x, y)</th>
                    <th class="text-end">Activities here</th>
                    <th>Visibility</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($locations as $location)
                    <tr>
                        <td>
                            <a href="{{ route('content.map-locations.show', $location) }}"
                               class="text-decoration-none fw-semibold">
                                {{ $location->name }}
                            </a>
                        </td>
                        <td class="text-capitalize">{{ $location->category }}</td>
                        <td class="text-end font-monospace small">
                            {{ $location->pos_x }}%, {{ $location->pos_y }}%
                        </td>
                        <td class="text-end">{{ $location->activities_count }}</td>
                        <td>
                            <span class="badge text-bg-{{ $location->is_visible ? 'success' : 'secondary' }}">
                                {{ $location->is_visible ? 'Visible' : 'Hidden' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('content.map-locations.edit', $location) }}"
                                   class="btn btn-outline-secondary">Edit</a>
                                <form method="POST"
                                      action="{{ route('content.map-locations.destroy', $location) }}"
                                      onsubmit="return confirm('Delete {{ $location->name }}? Hiding it is usually safer.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <x-shared.empty-state message="No locations on the island map yet.">
                                <a href="{{ route('content.map-locations.create') }}" class="btn btn-sm btn-primary">
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

<div class="mt-4">{{ $locations->links() }}</div>

@endsection
