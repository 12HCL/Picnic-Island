@extends('layouts.app')
@section('title', 'Park events')
@section('content')

<x-shared.page-header
    title="Park events"
    subtitle="Rides, shows and beach events — pick a day and book your place" />

<x-shared.photo-banner src="img/photos/park-events-banner.webp" alt="Aerial view of Picnic Island's roller coaster, beach and dolphin cove" />

{{-- Filters --}}
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('park.events.index') }}" class="row g-3">
            <div class="col-md-4">
                <label for="date" class="form-label small text-muted">Date</label>
                <input type="date" name="date" id="date" class="form-control form-control-sm"
                       value="{{ $filters['date'] ?? '' }}" min="{{ now()->toDateString() }}">
            </div>
            <div class="col-md-4">
                <label for="type" class="form-label small text-muted">Type</label>
                <select name="type" id="type" class="form-select form-select-sm">
                    <option value="">-- Everything --</option>
                    @foreach (['ride' => 'Rides', 'show' => 'Shows', 'beach_event' => 'Beach events'] as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['type'] ?? '') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                <a href="{{ route('park.events.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3">
    @forelse ($events as $event)
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h2 class="h5 card-title mb-0">{{ $event->activity->name }}</h2>
                        <span class="badge text-bg-light text-capitalize">
                            {{ str_replace('_', ' ', $event->activity->type) }}
                        </span>
                    </div>

                    <p class="text-body-secondary small mb-2">
                        {{ $event->event_date->format('D j M Y') }}
                        at {{ \Illuminate\Support\Carbon::parse($event->start_time)->format('H:i') }}
                    </p>

                    @if ($event->activity->description)
                        <p class="card-text small">{{ Str::limit($event->activity->description, 90) }}</p>
                    @endif

                    <div class="mt-auto pt-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">MVR {{ number_format((float) $event->price, 2) }}</div>
                            <div class="small {{ $event->seats_remaining <= 5 ? 'text-danger' : 'text-body-secondary' }}">
                                {{ $event->seats_remaining }} of {{ $event->capacity }} left
                            </div>
                        </div>
                        <a href="{{ route('park.events.show', $event) }}" class="btn btn-primary btn-sm">
                            View &amp; book
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <x-shared.empty-state message="Nothing is scheduled for that day. Try another date.">
                <a href="{{ route('park.events.index') }}" class="btn btn-sm btn-primary">Show everything</a>
            </x-shared.empty-state>
        </div>
    @endforelse
</div>

<div class="mt-4">{{ $events->links() }}</div>

@endsection
