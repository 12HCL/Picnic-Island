@extends('layouts.app')
@section('title', 'Add Map Location')
@section('content')

<x-shared.page-header
    title="Add a map location"
    subtitle="A clickable marker on the island map. Position it by percentage.">
    <a href="{{ route('content.map-locations.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Locations</a>
</x-shared.page-header>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ route('content.map-locations.store') }}">
                    @csrf
                    @include('content.map-locations._form')

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">Add to the map</button>
                        <a href="{{ route('content.map-locations.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- The map itself, as a reference for choosing the percentages. --}}
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-2">Where is it?</h2>
                <p class="small text-body-secondary">
                    Read the position off this image. Left edge is 0%, right edge 100%;
                    top is 0%, bottom 100%.
                </p>
                <img src="{{ asset('img/island-map-v2.webp') }}"
                     class="img-fluid rounded border"
                     alt="Island map, for choosing a position">
            </div>
        </div>
    </div>
</div>

@endsection
