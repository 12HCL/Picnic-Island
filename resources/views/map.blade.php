{{-- Module 5: public, database-driven interactive island map. --}}
@extends('layouts.app')

@section('title', 'Explore Picnic Island')

@push('styles')
    <link href="{{ asset('css/island-map.css') }}" rel="stylesheet">
@endpush

@section('content')
    <section class="map-hero mb-4">
        <div>
            <span class="map-eyebrow">Plan your island day</span>
            <h1 class="display-6 fw-bold mb-2">Explore Picnic Island</h1>
            <p class="lead mb-0">Choose a marker to discover beaches, attractions, facilities and arrival points.</p>
        </div>
        <div class="map-hero-stat" aria-label="{{ $locations->count() }} places available">
            <strong>{{ $locations->count() }}</strong>
            <span>places to explore</span>
        </div>
    </section>

    @if ($locations->isEmpty())
        <x-shared.empty-state message="No locations have been added to the map yet." />
    @else
        @php
            $categoryLabels = [
                'hotel' => 'Hotels',
                'jetty' => 'Jetties',
                'attraction' => 'Attractions',
                'beach' => 'Beaches',
                'facility' => 'Facilities',
            ];
            $categories = $locations->pluck('category')->unique()->values();
        @endphp

        <div class="map-toolbar mb-3" aria-label="Map filters">
            <div class="d-flex flex-wrap gap-2" role="group" aria-label="Filter locations by category">
                <button type="button" class="btn btn-sm map-filter active" data-map-filter="all" aria-pressed="true">All places</button>
                @foreach ($categories as $category)
                    <button type="button" class="btn btn-sm map-filter" data-map-filter="{{ $category }}" aria-pressed="false">
                        {{ $categoryLabels[$category] ?? ucfirst(str_replace('_', ' ', $category)) }}
                    </button>
                @endforeach
            </div>
            <span class="small text-body-secondary" id="map-result-count" aria-live="polite">Showing {{ $locations->count() }} places</span>
        </div>

        <div class="interactive-map" data-interactive-map>
            <div class="map-canvas">
                <img src="{{ asset($mapImage) }}"
                     class="map-artwork"
                     alt="Illustrated aerial map of Picnic Island showing the resort, beach, visitor centre, theme park, dolphin cove and two jetties">
                <div class="map-vignette" aria-hidden="true"></div>

                @foreach ($locations as $loc)
                    <button type="button"
                            class="map-marker map-marker--{{ $loc->category }}"
                            style="--marker-x: {{ $loc->pos_x }}%; --marker-y: {{ $loc->pos_y }}%;"
                            data-map-marker
                            data-location-id="{{ $loc->id }}"
                            data-category="{{ $loc->category }}"
                            data-name="{{ $loc->name }}"
                            data-description="{{ $loc->description ?: 'No description has been added for this location yet.' }}"
                            aria-label="Show {{ $loc->name }} details"
                            aria-controls="map-details">
                        <span class="map-marker-dot" aria-hidden="true"></span>
                        <span class="map-marker-label">{{ $loc->name }}</span>
                    </button>
                @endforeach

                <div class="map-compass" aria-hidden="true"><span>N</span><i></i></div>
            </div>

            <aside class="map-details" id="map-details" aria-live="polite">
                <div class="map-details-accent" aria-hidden="true"></div>
                <span class="badge rounded-pill map-details-category" id="map-details-category">Select a place</span>
                <h2 class="h3 mt-3 mb-2" id="map-details-title">Your island adventure starts here</h2>
                <p class="text-body-secondary mb-4" id="map-details-description">
                    Select any marker on the map or choose a place from the directory below.
                </p>
                <div class="map-tip mt-auto">
                    <span aria-hidden="true">&#9678;</span>
                    <p class="small mb-0">The map is interactive and fully keyboard accessible. Use Tab to move between places.</p>
                </div>
            </aside>
        </div>

        <section class="mt-5" aria-labelledby="place-directory-title">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-end gap-3 mb-3">
                <div>
                    <span class="map-eyebrow text-primary">Island directory</span>
                    <h2 class="h3 mb-0" id="place-directory-title">Places on the island</h2>
                </div>
                <label class="map-search">
                    <span class="visually-hidden">Search island places</span>
                    <input type="search" class="form-control" id="map-location-search" placeholder="Search places" autocomplete="off">
                </label>
            </div>

            <div class="row g-3" id="map-location-directory">
                @foreach ($locations as $loc)
                    <div class="col-md-6 col-xl-4 map-directory-item"
                         data-location-card="{{ $loc->id }}"
                         data-category="{{ $loc->category }}"
                         data-search="{{ strtolower($loc->name.' '.$loc->description.' '.$loc->category) }}">
                        <button type="button" class="card map-place-card h-100 w-100 text-start" data-location-select="{{ $loc->id }}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <span class="map-place-category">{{ $categoryLabels[$loc->category] ?? ucfirst($loc->category) }}</span>
                                        <h3 class="h5 mt-2 mb-2">{{ $loc->name }}</h3>
                                    </div>
                                    <span class="map-place-arrow" aria-hidden="true">&rarr;</span>
                                </div>
                                <p class="small text-body-secondary mb-0">
                                    {{ $loc->description ?: 'No description has been added for this location yet.' }}
                                </p>
                            </div>
                        </button>
                    </div>
                @endforeach
            </div>

            <p class="alert alert-light border mt-3 d-none" id="map-no-results" role="status">
                No island places match that search and filter.
            </p>
        </section>
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('js/island-map.js') }}" defer></script>
@endpush
