@extends('layouts.app')
@section('title', 'Hotels')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Hotels</h1>
</div>

@if ($hotels->isEmpty())
    <div class="alert alert-info">No hotels are listed yet.</div>
@else
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        @foreach ($hotels as $hotel)
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <h5 class="card-title mb-0">{{ $hotel->name }}</h5>
                            <span class="badge bg-warning text-dark ms-2">
                                @for ($i = 0; $i < $hotel->star_rating; $i++)★@endfor
                            </span>
                        </div>
                        <p class="text-muted small mb-2">{{ $hotel->address }}</p>
                        <p class="card-text small">{{ Str::limit($hotel->description, 120) }}</p>
                        <p class="small text-secondary mb-0">{{ $hotel->rooms_count }} room(s) available</p>
                    </div>
                    <div class="card-footer bg-transparent border-top-0">
                        <a href="{{ route('hotel.show', $hotel) }}" class="btn btn-primary btn-sm w-100">
                            View &amp; Book
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection
