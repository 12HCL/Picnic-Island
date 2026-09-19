@extends('layouts.app')
@section('title', $location->name)
@section('content')

<x-shared.page-header
    title="{{ $location->name }}"
    subtitle="Map location — {{ ucfirst($location->category) }}">
    <a href="{{ route('content.map-locations.edit', $location) }}" class="btn btn-primary btn-sm">Edit</a>
    <a href="{{ route('content.map-locations.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Locations</a>
</x-shared.page-header>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5 small text-muted">Category</dt>
                    <dd class="col-sm-7 text-capitalize">{{ $location->category }}</dd>

                    <dt class="col-sm-5 small text-muted">Position</dt>
                    <dd class="col-sm-7 font-monospace">{{ $location->pos_x }}%, {{ $location->pos_y }}%</dd>

                    <dt class="col-sm-5 small text-muted">On the public map</dt>
                    <dd class="col-sm-7">
                        <span class="badge text-bg-{{ $location->is_visible ? 'success' : 'secondary' }}">
                            {{ $location->is_visible ? 'Visible' : 'Hidden' }}
                        </span>
                    </dd>

                    <dt class="col-sm-5 small text-muted">Added</dt>
                    <dd class="col-sm-7">{{ $location->created_at?->format('j M Y') ?? '—' }}</dd>

                    <dt class="col-sm-5 small text-muted">Last updated</dt>
                    <dd class="col-sm-7">{{ $location->updated_at?->format('j M Y') ?? '—' }}</dd>
                </dl>

                <hr>

                <h2 class="h6">Description</h2>
                <p class="mb-0 text-body-secondary">
                    {{ $location->description ?: 'No description has been added for this location yet.' }}
                </p>
            </div>
        </div>

        {{-- Seam 3, BUILD_CONTRACT.md §6 — Module 4 reads this table. --}}
        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <h2 class="h6 mb-3">Park activities placed here</h2>

                @forelse ($location->activities as $activity)
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                        <span>{{ $activity->name }}</span>
                        <span class="badge text-bg-light border text-capitalize">
                            {{ str_replace('_', ' ', $activity->type) }}
                        </span>
                    </div>
                @empty
                    <p class="small text-body-secondary mb-0">
                        Nothing from the theme park is placed at this location.
                    </p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3">Position on the map</h2>
                <div class="position-relative">
                    <img src="{{ asset('img/island-map-v2.png') }}"
                         class="img-fluid rounded border d-block"
                         alt="Island map with {{ $location->name }} marked">
                    <span class="position-absolute badge rounded-pill bg-danger shadow"
                          style="left: {{ $location->pos_x }}%; top: {{ $location->pos_y }}%; transform: translate(-50%, -50%);">
                        {{ $location->name }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
