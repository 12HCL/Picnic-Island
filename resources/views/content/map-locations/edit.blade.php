@extends('layouts.app')
@section('title', 'Edit Map Location')
@section('content')

<x-shared.page-header
    title="Edit {{ $location->name }}"
    subtitle="Changes appear on the public map immediately.">
    <a href="{{ route('content.map-locations.show', $location) }}" class="btn btn-outline-secondary btn-sm">View</a>
    <a href="{{ route('content.map-locations.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Locations</a>
</x-shared.page-header>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ route('content.map-locations.update', $location) }}">
                    @csrf
                    @method('PUT')
                    @include('content.map-locations._form')

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">Save changes</button>
                        <a href="{{ route('content.map-locations.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Preview: the map with this marker where it currently sits, so a reposition
         can be judged before saving. --}}
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-2">Current position</h2>
                <p class="small text-body-secondary">
                    This shows the saved position, not what is typed in the form.
                    Save to move the marker.
                </p>
                <div class="position-relative">
                    <img src="{{ asset('img/island-map.svg') }}"
                         class="img-fluid rounded border d-block"
                         alt="Island map with this location marked">
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
