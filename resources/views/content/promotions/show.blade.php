@extends('layouts.app')
@section('title', $promotion->title)
@section('content')

<x-shared.page-header
    :title="$promotion->title"
    subtitle="Promotion detail. UC-18.">
    <a href="{{ route('content.promotions.edit', $promotion) }}" class="btn btn-primary btn-sm">Edit</a>
    <a href="{{ route('content.promotions.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Promotions</a>
</x-shared.page-header>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            @if ($promotion->image_path)
                <img src="{{ Storage::url($promotion->image_path) }}" class="card-img-top" alt="">
            @endif
            <div class="card-body">
                @if ($promotion->body)
                    <p class="mb-0">{{ $promotion->body }}</p>
                @else
                    <p class="text-body-secondary mb-0">No body text was written for this promotion.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Details</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-body-secondary">Module</span>
                    <span class="text-capitalize">{{ $promotion->module }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-body-secondary">Runs</span>
                    <span>
                        {{ $promotion->starts_on->format('j M Y') }}
                        &ndash;
                        {{ $promotion->ends_on->format('j M Y') }}
                    </span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-body-secondary">Status</span>
                    <span>
                        @if (! $promotion->is_published)
                            <span class="badge text-bg-secondary">Draft</span>
                        @elseif ($promotion->ends_on->isPast())
                            <span class="badge text-bg-secondary">Finished</span>
                        @elseif ($promotion->starts_on->isFuture())
                            <span class="badge text-bg-info">Scheduled</span>
                        @else
                            <span class="badge text-bg-success">Live</span>
                        @endif
                    </span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-body-secondary">Created by</span>
                    {{-- promotions.created_by is NOT NULL and is this entity's only user
                         relationship — MASTER_SCHEMA.md §15. --}}
                    <span>{{ $promotion->creator?->name ?? 'Unknown' }}</span>
                </li>
            </ul>
        </div>
    </div>
</div>

@endsection
