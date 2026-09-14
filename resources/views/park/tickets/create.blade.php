@extends('layouts.app')
@section('title', 'Confirm and Pay')
@section('content')

<x-shared.page-header
    title="Confirm and pay"
    subtitle="Nothing is charged until you confirm — this is a simulated payment.">
    <a href="{{ route('park.events.show', $event) }}" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
</x-shared.page-header>

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-transparent">What you are buying</div>
            <div class="card-body">
                <h2 class="h5">{{ $event->activity->name }}</h2>
                <p class="text-body-secondary small mb-3 text-capitalize">
                    {{ str_replace('_', ' ', $event->activity->type) }}
                </p>

                <dl class="row mb-0 small">
                    <dt class="col-5 text-body-secondary">Date</dt>
                    <dd class="col-7">{{ $event->event_date->format('l j F Y') }}</dd>

                    <dt class="col-5 text-body-secondary">Starts</dt>
                    <dd class="col-7">{{ \Illuminate\Support\Carbon::parse($event->start_time)->format('H:i') }}</dd>

                    <dt class="col-5 text-body-secondary">Admissions</dt>
                    <dd class="col-7">{{ $quantity }}</dd>

                    <dt class="col-5 text-body-secondary">Price each</dt>
                    <dd class="col-7">MVR {{ number_format((float) $event->price, 2) }}</dd>
                </dl>

                <hr>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Total</span>
                    <span class="h5 mb-0">
                        MVR {{ number_format((float) $event->price * $quantity, 2) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-transparent">Payment</div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('park.tickets.store') }}">
                    @csrf
                    <input type="hidden" name="park_event_id" value="{{ $event->id }}">
                    <input type="hidden" name="quantity" value="{{ $quantity }}">

                    <label for="method" class="form-label small text-muted">Payment method</label>
                    <select name="method" id="method" class="form-select mb-3">
                        @foreach ($methods as $method)
                            <option value="{{ $method }}" {{ old('method') === $method ? 'selected' : '' }}>
                                {{ ucfirst($method) }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit" class="btn btn-primary w-100">
                        Pay MVR {{ number_format((float) $event->price * $quantity, 2) }}
                    </button>
                </form>

                <p class="text-body-secondary small mt-3 mb-0">
                    Your ticket is issued the moment payment is confirmed. If the event sells out
                    while you are on this page the payment is refused and nothing is charged.
                </p>
            </div>
        </div>
    </div>
</div>

@endsection
