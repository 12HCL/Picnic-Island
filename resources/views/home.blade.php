{{--
    The public front page. Module 5, Content. BUILD_CONTRACT.md §3.

    Laid out from the reference designs: a full-bleed hero with the words over the photo,
    a search strip beneath it, featured stays as cards, then offers, what's on, and the
    island map as a band above the footer.

    The strip searches sailings rather than rooms because the ferry timetable is the one
    index that already filters on route and date. The hotel list does not filter yet, so a
    room search here would be a form that promises something the controller cannot do.
--}}
@extends('layouts.app')

@section('title', 'Welcome')

@section('content')

{{-- ── Hero ─────────────────────────────────────────────────────────────── --}}
<div class="pi-bleed pi-hero mb-0" style="background-image: url('{{ asset('img/photos/home-hero.webp') }}')">
    <div>
        <span class="pi-eyebrow">A picnic island in the Maldives</span>
        <h1 class="mt-2 mb-3">Stay, cross, and spend the day</h1>
        <p class="fs-5 mb-4 opacity-85">
            A hotel on a quiet island, a ferry across, and a theme park next door.
            Book your room first, then your crossing, then your day out.
        </p>
        <div class="d-flex flex-wrap gap-2 justify-content-center">
            <a href="{{ route('hotel.index') }}" class="btn btn-primary btn-lg px-4">Find a room</a>
            <a href="{{ route('park.events.index') }}" class="btn btn-outline-light btn-lg px-4">Park events</a>
        </div>
    </div>
</div>

{{-- ── Crossing search strip ────────────────────────────────────────────── --}}
<div class="pi-bleed pi-strip py-4 mb-5">
    <div class="container">
        <form method="GET" action="{{ route('ferry.schedules.index') }}" class="row g-3 align-items-end">
            <div class="col-12">
                <span class="pi-eyebrow">Find a crossing</span>
            </div>
            <div class="col-md-5">
                <label for="route" class="form-label">Crossing</label>
                <select name="route" id="route" class="form-select">
                    <option value="">Every crossing</option>
                    @foreach ($ferryRoutes as $ferryRoute)
                        <option value="{{ $ferryRoute->id }}">
                            {{ $ferryRoute->origin }} to {{ $ferryRoute->destination }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="date" class="form-label">Sailing date</label>
                <input type="date" name="date" id="date" class="form-control" min="{{ now()->toDateString() }}">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Show sailings</button>
            </div>
        </form>
    </div>
</div>

{{-- The rule the whole system is built around, said plainly to a visitor rather than
     left for them to discover at the point of refusal. --}}
<div class="alert alert-info d-flex align-items-start gap-2">
    <strong class="flex-shrink-0">Before you book a crossing:</strong>
    <span>
        Ferry tickets are only issued to visitors who already hold a confirmed hotel
        booking covering the sailing date. Book your room first, then your crossing.
    </span>
</div>

{{-- ── Featured stays ───────────────────────────────────────────────────── --}}
@if ($hotels->isNotEmpty())
    <h2 class="h4 pi-section-title">Featured stays</h2>

    <div class="row g-4 mb-5">
        @foreach ($hotels as $hotel)
            <div class="col-sm-6 col-lg-3">
                <div class="card h-100 shadow-sm pi-lift">
                    @if ($photo = config('photos.hotels.'.$hotel->name))
                        <img src="{{ asset($photo) }}" class="card-img-top" alt="{{ $hotel->name }}"
                             style="height: 11rem; object-fit: cover;">
                    @endif
                    <div class="card-body">
                        <div class="pi-stars small mb-1">
                            @for ($i = 0; $i < $hotel->star_rating; $i++)★@endfor
                        </div>
                        <h3 class="h6 mb-1">{{ $hotel->name }}</h3>
                        <p class="small text-body-secondary mb-0">{{ $hotel->address }}</p>
                    </div>
                    <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                        @if ($hotel->room_types_min_base_price)
                            <span class="small text-body-secondary">
                                from <strong class="text-primary">MVR {{ number_format((float) $hotel->room_types_min_base_price, 0) }}</strong> / night
                            </span>
                        @endif
                        <a href="{{ route('hotel.show', $hotel) }}" class="btn btn-sm btn-outline-primary">View</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- ── Current offers ───────────────────────────────────────────────────── --}}
@if ($promotions->isNotEmpty())
    <h2 class="h4 pi-section-title">Current offers</h2>

    <div class="row g-4 mb-5">
        @foreach ($promotions as $promotion)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm pi-lift">
                    @if ($promotion->image_path)
                        <img src="{{ asset('storage/' . $promotion->image_path) }}"
                             class="card-img-top" alt="" style="height: 10rem; object-fit: cover;">
                    @endif

                    <div class="card-body">
                        <span class="badge text-bg-light border text-capitalize mb-2">
                            {{ $promotion->module }}
                        </span>
                        <h3 class="h6">{{ $promotion->title }}</h3>
                        <p class="small text-body-secondary mb-0">{{ $promotion->body }}</p>
                    </div>

                    <div class="card-footer bg-transparent small text-body-secondary">
                        Until {{ $promotion->ends_on->format('j M Y') }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- ── Coming up at the park ────────────────────────────────────────────── --}}
@if ($featuredEvents->isNotEmpty())
    <h2 class="h4 pi-section-title">Coming up at the park</h2>

    <div class="row g-4 mb-5">
        @foreach ($featuredEvents as $event)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm pi-lift">
                    <div class="card-body">
                        <span class="badge text-bg-light border text-capitalize mb-2">
                            {{ str_replace('_', ' ', $event->activity->type) }}
                        </span>
                        <h3 class="h6 mb-1">{{ $event->activity->name }}</h3>
                        <p class="small text-body-secondary mb-2">
                            {{ $event->event_date->format('j M Y') }}
                            at {{ substr($event->start_time, 0, 5) }}
                        </p>
                        <p class="fw-semibold mb-0 text-primary">
                            MVR {{ number_format((float) $event->price, 2) }}
                        </p>
                    </div>

                    <div class="card-footer bg-transparent">
                        <a href="{{ route('park.events.show', $event) }}"
                           class="btn btn-sm btn-outline-primary">Details</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- ── The island map, as a band above the footer ───────────────────────── --}}
<div class="pi-bleed pi-map-band" style="background-image: url('{{ asset('img/island-map-v2.webp') }}')">
    <div class="container">
        <div class="col-lg-6 text-white py-5">
            <span class="pi-eyebrow">Plan your island day</span>
            <h2 class="h3 mt-2 mb-2">Find your way around</h2>
            <p class="mb-4 opacity-85">
                Beaches, jetties, the resort and the park, all on one interactive map.
                Choose a marker to see what is there.
            </p>
            <a href="{{ route('content.map') }}" class="btn btn-primary btn-lg px-4">Open the island map</a>
        </div>
    </div>
</div>

@endsection
