{{--
    The public front page. Module 5, Content. BUILD_CONTRACT.md §3.

    Replaces scaffold-placeholder.blade.php, which said "Scaffold is running" and was
    always meant to be deleted once this existed.

    DELIBERATELY MINIMAL — SAFHAAN, THE REST IS YOURS.
    The contract gives this page live promotions and featured park events. Neither is here.
    This is a front door, not the content page the contract describes: it exists so the
    first thing a marker sees is not an apology, and it stops short so the content work is
    still yours to write and to claim.

    Everything it links to is already built. A front page advertising routes nobody has
    written is worse than a plain one.
--}}
@extends('layouts.app')

@section('title', 'Welcome')

@section('content')

<div class="p-4 p-md-5 mb-4 rounded-3 bg-primary text-white shadow-sm">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <h1 class="display-5 fw-semibold mb-2">Picnic Island</h1>
            <p class="fs-5 mb-4 opacity-75">
                A hotel on a quiet island, a ferry across, and a theme park next door.
                Book your stay, then your crossing, then your day out.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('hotel.index') }}" class="btn btn-light btn-lg">Find a room</a>
                <a href="{{ route('ferry.schedules.index') }}" class="btn btn-outline-light btn-lg">Ferry times</a>
                <a href="{{ route('park.events.index') }}" class="btn btn-outline-light btn-lg">What's on</a>
            </div>
        </div>
        <div class="col-lg-5 d-none d-lg-block">
            <img src="{{ asset('img/island-map.svg') }}" class="img-fluid rounded" alt="">
        </div>
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

<div class="row g-3">
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h2 class="h6">Stay</h2>
                <p class="small text-body-secondary">
                    Rooms at the island hotels, with availability by date.
                </p>
                <a href="{{ route('hotel.index') }}" class="btn btn-sm btn-outline-primary">Browse hotels</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h2 class="h6">Cross</h2>
                <p class="small text-body-secondary">
                    Ferry sailings between the mainland and the island.
                </p>
                <a href="{{ route('ferry.schedules.index') }}" class="btn btn-sm btn-outline-primary">Ferry timetable</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h2 class="h6">Explore</h2>
                <p class="small text-body-secondary">
                    Rides, shows and beach events, and the island map.
                </p>
                <a href="{{ route('park.events.index') }}" class="btn btn-sm btn-outline-primary">What's on</a>
                <a href="{{ route('content.map') }}" class="btn btn-sm btn-outline-secondary">Island map</a>
            </div>
        </div>
    </div>
</div>

@endsection
