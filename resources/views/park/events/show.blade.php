@extends('layouts.app')
@section('title', $event->activity->name)
@section('content')

<x-shared.page-header
    :title="$event->activity->name"
    :subtitle="$event->event_date->format('l j F Y') . ' at ' . \Illuminate\Support\Carbon::parse($event->start_time)->format('H:i')">
    <a href="{{ route('park.events.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Back to listing</a>
</x-shared.page-header>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex gap-2 mb-3">
                    <span class="badge text-bg-light text-capitalize">
                        {{ str_replace('_', ' ', $event->activity->type) }}
                    </span>
                    <x-shared.status-badge :status="$event->status" />
                </div>

                <p class="mb-4">{{ $event->activity->description ?: 'No description provided.' }}</p>

                <dl class="row mb-0 small">
                    <dt class="col-5 text-body-secondary">Date</dt>
                    <dd class="col-7">{{ $event->event_date->format('D j M Y') }}</dd>

                    <dt class="col-5 text-body-secondary">Starts</dt>
                    <dd class="col-7">{{ \Illuminate\Support\Carbon::parse($event->start_time)->format('H:i') }}</dd>

                    <dt class="col-5 text-body-secondary">Where</dt>
                    <dd class="col-7">{{ $event->activity->mapLocation->name ?? 'Announced on the day' }}</dd>

                    <dt class="col-5 text-body-secondary">Capacity</dt>
                    <dd class="col-7">{{ $event->capacity }}</dd>

                    <dt class="col-5 text-body-secondary">Still available</dt>
                    <dd class="col-7 {{ $seatsRemaining <= 5 ? 'text-danger fw-semibold' : '' }}">
                        {{ $seatsRemaining }}
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="h4 mb-1">MVR {{ number_format((float) $event->price, 2) }}</div>
                <p class="text-body-secondary small">per admission</p>

                @if ($seatsRemaining < 1)
                    <div class="alert alert-warning mb-0">This event is sold out.</div>
                @elseif (! auth()->check())
                    <a href="{{ route('login') }}" class="btn btn-primary w-100">Log in to book</a>
                    <p class="text-body-secondary small mt-2 mb-0">
                        You need an account to buy online. Tickets are also sold at the park entrance.
                    </p>
                @else
                    {{--
                        GET, not POST: this only carries the choice through to the confirmation
                        screen. Nothing is written until payment is confirmed there —
                        MASTER_SCHEMA.md §14.
                    --}}
                    <form method="GET" action="{{ route('park.tickets.create') }}">
                        <input type="hidden" name="park_event_id" value="{{ $event->id }}">

                        <label for="quantity" class="form-label small text-muted">Admissions</label>
                        <select name="quantity" id="quantity" class="form-select mb-3">
                            @for ($i = 1; $i <= min(10, $seatsRemaining); $i++)
                                <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                        </select>

                        <button type="submit" class="btn btn-primary w-100">Continue to payment</button>
                    </form>
                @endif
            </div>
        </div>

        @if ($alternatives->isNotEmpty())
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-transparent">Other dates</div>
                <ul class="list-group list-group-flush">
                    @foreach ($alternatives as $alt)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="small">
                                {{ $alt->event_date->format('D j M') }}
                                at {{ \Illuminate\Support\Carbon::parse($alt->start_time)->format('H:i') }}
                            </span>
                            <a href="{{ route('park.events.show', $alt) }}" class="btn btn-sm btn-outline-primary">
                                View
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>

@endsection
