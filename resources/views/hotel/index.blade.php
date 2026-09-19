@extends('layouts.app')
@section('title', 'Hotels')
@section('content')

<x-shared.page-header
    title="Hotels"
    subtitle="Where to stay on the island — book a room before your ferry crossing" />

@if ($hotels->isEmpty())
    <div class="alert alert-info">No hotels are listed yet.</div>
@else
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        @foreach ($hotels as $hotel)
            <div class="col">
                <div class="card h-100 shadow-sm pi-lift">
                    @if ($photo = config('photos.hotels.'.$hotel->name))
                        <img src="{{ asset($photo) }}" class="card-img-top" alt="{{ $hotel->name }}"
                             style="height: 12rem; object-fit: cover;">
                    @endif
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <h5 class="card-title mb-0">{{ $hotel->name }}</h5>
                            <span class="pi-stars ms-2">
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
