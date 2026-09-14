@extends('layouts.app')
@section('title', 'Gate Sales')
@section('content')

<x-shared.page-header
    title="Sell at the gate"
    subtitle="At-entrance admissions. No account needed for the buyer." />

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('park.staff.gate-sale') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="date" class="form-label small text-muted">Selling for</label>
                <input type="date" name="date" id="date" class="form-control form-control-sm"
                       value="{{ $date }}">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary btn-sm">Show that day</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3">
    @forelse ($events as $event)
        @php $remaining = $event->seatsRemaining(); @endphp

        <div class="col-md-6 col-xl-4">
            <div class="card h-100 shadow-sm {{ $remaining < 1 ? 'opacity-75' : '' }}">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <h2 class="h6 mb-0">{{ $event->activity->name }}</h2>
                        <span class="badge text-bg-light">
                            {{ \Illuminate\Support\Carbon::parse($event->start_time)->format('H:i') }}
                        </span>
                    </div>

                    <p class="small text-body-secondary mb-2">
                        MVR {{ number_format((float) $event->price, 2) }} each
                        &middot;
                        <span class="{{ $remaining <= 5 ? 'text-danger fw-semibold' : '' }}">
                            {{ $remaining }} of {{ $event->capacity }} left
                        </span>
                    </p>

                    @if ($remaining < 1)
                        <div class="alert alert-warning py-2 px-3 small mb-0 mt-auto">
                            Sold out — tell the customer this one is full.
                        </div>
                    @else
                        <form method="POST" action="{{ route('park.staff.gate-sale.store') }}" class="mt-auto">
                            @csrf
                            <input type="hidden" name="park_event_id" value="{{ $event->id }}">

                            <div class="row g-2">
                                <div class="col-5">
                                    <label for="quantity-{{ $event->id }}" class="form-label small text-muted mb-1">
                                        Admissions
                                    </label>
                                    <select name="quantity" id="quantity-{{ $event->id }}"
                                            class="form-select form-select-sm">
                                        @for ($i = 1; $i <= min(10, $remaining); $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-7">
                                    <label for="method-{{ $event->id }}" class="form-label small text-muted mb-1">
                                        Paid by
                                    </label>
                                    <select name="method" id="method-{{ $event->id }}"
                                            class="form-select form-select-sm">
                                        @foreach ($methods as $method)
                                            <option value="{{ $method }}"
                                                {{ $method === 'cash' ? 'selected' : '' }}>
                                                {{ ucfirst($method) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-sm w-100 mt-2">Sell</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <x-shared.empty-state message="Nothing is scheduled for that day, so there is nothing to sell.">
                <a href="{{ route('park.staff.activities.index') }}" class="btn btn-sm btn-primary">
                    Activity catalogue
                </a>
            </x-shared.empty-state>
        </div>
    @endforelse
</div>

@endsection
