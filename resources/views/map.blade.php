{{--
    Module 5 — Content, Map & Reporting. Owner: Ahmed Safhaan.
    BUILD_CONTRACT.md §3, Module 5 · UC-08 "View Island Map and Promotions".

    Receives from Content\MapController@index:
      $locations  Collection of MapLocation (visible only)
      $mapImage   string, a path under public/

    No JavaScript. Each marker is an <a> whose href jumps to that location's card
    in the list below, which is also UC-08's accessible fallback (A3 / E2).
--}}
@extends('layouts.app')

@section('title', 'Island map')

@section('content')

    <x-shared.page-header
        title="Island map"
        subtitle="Select a marker to read about that part of the island." />

    @if ($locations->isEmpty())

        <x-shared.empty-state message="No locations have been added to the map yet." />

    @else

        {{-- ============ The map ============
             position-relative on the wrapper is what makes position-absolute on each
             marker measure from this box rather than from the whole page.
             The image is img-fluid, so it scales; because the markers are positioned
             in PERCENTAGES they scale with it and stay in the right place. --}}
        <div class="position-relative border rounded-3 overflow-hidden shadow-sm mb-4">

            <img src="{{ asset($mapImage) }}"
                 class="img-fluid w-100 d-block"
                 alt="Illustrated map of Picnic Island">

            @foreach ($locations as $loc)
                <a href="#loc-{{ $loc->id }}"
                   class="position-absolute badge rounded-pill text-decoration-none shadow
                          bg-{{ $loc->category === 'hotel'      ? 'danger'
                             : ($loc->category === 'jetty'      ? 'dark'
                             : ($loc->category === 'beach'      ? 'warning text-dark'
                             : ($loc->category === 'facility'   ? 'secondary'
                             : 'primary'))) }}"
                   style="left: {{ $loc->pos_x }}%; top: {{ $loc->pos_y }}%; transform: translate(-50%, -50%);"
                   title="{{ $loc->name }}">
                    {{ $loc->name }}
                </a>
            @endforeach

        </div>

        {{-- ============ Legend ============ --}}
        <p class="small text-body-secondary">
            <span class="badge rounded-pill bg-danger">Hotel</span>
            <span class="badge rounded-pill bg-dark">Jetty</span>
            <span class="badge rounded-pill bg-primary">Attraction</span>
            <span class="badge rounded-pill bg-warning text-dark">Beach</span>
            <span class="badge rounded-pill bg-secondary">Facility</span>
        </p>

        {{-- ============ Location details ============
             Each card carries the id the markers link to. This is also the
             text-only directory UC-08 falls back to if the image fails to load. --}}
        <h2 class="h5 mt-4 mb-3">Places on the island</h2>

        <div class="row g-3">
            @foreach ($locations as $loc)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100" id="loc-{{ $loc->id }}">
                        <div class="card-body">
                            <h3 class="card-title h6 mb-1">{{ $loc->name }}</h3>

                            <p class="mb-2">
                                <span class="badge rounded-pill text-bg-light border">
                                    {{ ucfirst(str_replace('_', ' ', $loc->category)) }}
                                </span>
                            </p>

                            <p class="card-text small text-body-secondary mb-0">
                                {{ $loc->description ?: 'No description has been added for this location yet.' }}
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    @endif

@endsection
