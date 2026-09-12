@extends('layouts.app')
@section('title', $hotel->name)
@section('content')

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('hotel.index') }}">Hotels</a></li>
        <li class="breadcrumb-item active">{{ $hotel->name }}</li>
    </ol>
</nav>

<div class="row g-4">
    {{-- Hotel info --}}
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h4">{{ $hotel->name }}</h2>
                <p class="text-warning fs-5 mb-1">
                    @for ($i = 0; $i < $hotel->star_rating; $i++)★@endfor
                </p>
                <p class="text-muted small mb-3">{{ $hotel->address }}</p>
                <p>{{ $hotel->description }}</p>
            </div>
        </div>
    </div>

    {{-- Room types --}}
    <div class="col-lg-8">
        <h3 class="h5 mb-3">Room Types</h3>
        @forelse ($hotel->roomTypes as $type)
            <div class="card mb-3 shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="mb-1">{{ $type->name }}</h6>
                        <p class="text-muted small mb-0">{{ $type->description }}</p>
                        <span class="small">Max occupancy: <strong>{{ $type->max_occupancy }}</strong></span>
                    </div>
                    <div class="text-end">
                        <p class="fs-5 fw-semibold mb-1">MVR {{ number_format($type->base_price, 2) }}<span class="text-muted small fw-normal"> / night</span></p>
                        @auth
                            <a href="{{ route('hotel.bookings.create', ['hotel_id' => $hotel->id]) }}"
                               class="btn btn-primary btn-sm">Book Now</a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">Log in to Book</a>
                        @endauth
                    </div>
                </div>
            </div>
        @empty
            <p class="text-muted">No room types have been configured for this hotel yet.</p>
        @endforelse
    </div>
</div>

@endsection
