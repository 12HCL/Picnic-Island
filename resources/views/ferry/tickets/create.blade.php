{{--
    ferry.tickets.create — the page the whole brief turns on.
    Module 3, Ferry. Owner: Ali Naayif. BUILD_CONTRACT.md §3.

    BR-01: a ferry ticket may only be issued to a visitor holding a valid hotel booking
    that covers the sailing date. $eligibleBookings comes from HotelBookingGateway in the
    controller, so the refusal is a server decision.

    The choice is between the WHOLE FORM and the REFUSAL, so it is an @if/@else on
    $eligibleBookings->isEmpty() and never a @forelse — an @empty branch inside the loop
    would put a <div> where <option> elements belong, which browsers silently drop.
--}}
@extends('layouts.app')

@section('title', 'Book a ferry crossing')

@section('content')

    <x-shared.page-header
        title="Book a ferry crossing"
        :subtitle="$schedule->route->origin . ' to ' . $schedule->route->destination"
    />

    <div class="row g-4">

        {{-- The sailing --}}
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <img src="{{ asset('img/photos/ferry-crossing.jpg') }}" class="card-img-top"
                     alt="A ferry crossing at sunset" style="height: 12rem; object-fit: cover; object-position: center 70%;">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Sailing details</span>
                    <x-shared.status-badge :status="$schedule->status" />
                </div>

                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-body-secondary">Route</span>
                        <span>{{ $schedule->route->origin }} &rarr; {{ $schedule->route->destination }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-body-secondary">Departs</span>
                        <span>
                            {{ $schedule->departure_date->format('D j M Y') }}
                            at {{ \Illuminate\Support\Carbon::parse($schedule->departure_time)->format('H:i') }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-body-secondary">Crossing time</span>
                        <span>{{ $schedule->route->duration_minutes }} minutes</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-body-secondary">Vessel</span>
                        <span>{{ $schedule->vessel->name }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-body-secondary">Seats remaining</span>
                        <span>
                            {{ $schedule->seatsRemaining() }}
                            <span class="text-body-secondary">of {{ $schedule->vessel->capacity }}</span>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-body-secondary">Fare</span>
                        <span class="fw-semibold">{{ number_format($schedule->route->base_fare, 2) }}</span>
                    </li>
                </ul>
            </div>
        </div>

        {{-- BR-01: the refusal, or the form. Never both, never neither. --}}
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Your ferry ticket</div>
                <div class="card-body">

                    @if ($eligibleBookings->isEmpty())

                        <div class="alert alert-warning mb-0">
                            <h2 class="h6 alert-heading">A hotel booking is required</h2>
                            <p class="mb-2">
                                You need a confirmed hotel booking covering
                                {{ $schedule->departure_date->format('j F Y') }} before a ferry
                                ticket can be issued for this sailing.
                            </p>
                            <a href="{{ \Illuminate\Support\Facades\Route::has('hotel.index') ? route('hotel.index') : url('/') }}"
                               class="btn btn-sm btn-warning">
                                Book a room first
                            </a>
                        </div>

                    @else

                        <form method="POST" action="{{ route('ferry.tickets.store') }}">
                            @csrf

                            <input type="hidden" name="ferry_schedule_id" value="{{ $schedule->id }}">

                            <div class="mb-3">
                                <label for="hotel_booking_id" class="form-label">
                                    Hotel booking authorising this crossing
                                </label>

                                <select name="hotel_booking_id"
                                        id="hotel_booking_id"
                                        class="form-select @error('hotel_booking_id') is-invalid @enderror"
                                        required>
                                    @foreach ($eligibleBookings as $booking)
                                        <option value="{{ $booking->id }}"
                                                @selected(old('hotel_booking_id') == $booking->id)>
                                            {{ $booking->reference }} &mdash; {{ $booking->hotel->name }}
                                            ({{ $booking->check_in->format('j M') }} to {{ $booking->check_out->format('j M') }})
                                        </option>
                                    @endforeach
                                </select>

                                @error('hotel_booking_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror

                                <div class="form-text">
                                    Every ferry ticket is issued against the hotel booking that
                                    authorises it. This is recorded on the ticket.
                                </div>
                            </div>

                            {{-- Payment is a simulated confirmation, so this form is also the
                                 pay screen: the ticket row is written only when it is submitted
                                 (MASTER_SCHEMA.md §11). --}}
                            <div class="mb-3">
                                <label for="method" class="form-label">Payment method</label>
                                <select name="method" id="method"
                                        class="form-select @error('method') is-invalid @enderror" required>
                                    @foreach ($methods as $method)
                                        <option value="{{ $method }}" @selected(old('method') === $method)>
                                            {{ ucfirst($method) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-body-tertiary rounded">
                                <span class="text-body-secondary">Total to pay</span>
                                <span class="fs-5 fw-semibold">
                                    {{ number_format($schedule->route->base_fare, 2) }}
                                </span>
                            </div>

                            @if ($schedule->seatsRemaining() < 1)
                                <div class="alert alert-danger">
                                    This sailing is full. Please choose another crossing.
                                </div>
                            @else
                                <button type="submit" class="btn btn-primary">
                                    Confirm and pay
                                </button>
                            @endif
                        </form>

                    @endif

                </div>
            </div>
        </div>

    </div>

@endsection
