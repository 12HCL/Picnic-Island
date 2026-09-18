{{--
    ferry.tickets.show — one issued pass. Module 3, Ferry.
    Visible to the passenger who owns it and to any ferry operator; the check is in the
    controller, because role middleware cannot express "the owner or this one staff role".
--}}
@extends('layouts.app')
@section('title', 'Ferry ticket '.$ticket->reference)
@section('content')

<x-shared.page-header
    :title="'Ferry ticket '.$ticket->reference"
    :subtitle="$ticket->schedule->route->origin.' to '.$ticket->schedule->route->destination">
    <a href="{{ route('ferry.schedules.index') }}" class="btn btn-outline-secondary">All sailings</a>
</x-shared.page-header>

<div class="row g-4">

    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Boarding pass</span>
                <x-shared.status-badge :status="$ticket->status" />
            </div>

            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-body-secondary">Reference</span>
                    <span class="font-monospace">{{ $ticket->reference }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-body-secondary">Passenger</span>
                    <span>{{ $ticket->user->name }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-body-secondary">Crossing</span>
                    <span>
                        {{ $ticket->schedule->route->origin }} &rarr;
                        {{ $ticket->schedule->route->destination }}
                    </span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-body-secondary">Departs</span>
                    <span>
                        {{ $ticket->schedule->departure_date->format('D j M Y') }}
                        at {{ \Illuminate\Support\Carbon::parse($ticket->schedule->departure_time)->format('H:i') }}
                    </span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-body-secondary">Vessel</span>
                    <span>{{ $ticket->schedule->vessel->name }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-body-secondary">Fare paid</span>
                    <span class="fw-semibold">{{ number_format($ticket->fare, 2) }}</span>
                </li>
                @if ($ticket->payment)
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-body-secondary">Payment</span>
                        <span>
                            <span class="font-monospace">{{ $ticket->payment->reference }}</span>
                            <span class="text-body-secondary">({{ ucfirst($ticket->payment->method) }})</span>
                        </span>
                    </li>
                @endif
            </ul>
        </div>
    </div>

    <div class="col-lg-5">
        {{-- BR-01 made visible. The booking below is not decoration: hotel_booking_id is
             NOT NULL, so this ticket could not exist without it. --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold">Authorised by hotel booking</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-body-secondary">Reference</span>
                    <span class="font-monospace">{{ $ticket->hotelBooking->reference }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-body-secondary">Hotel</span>
                    <span>{{ $ticket->hotelBooking->hotel->name }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-body-secondary">Stay</span>
                    <span>
                        {{ $ticket->hotelBooking->check_in->format('j M') }} to
                        {{ $ticket->hotelBooking->check_out->format('j M Y') }}
                    </span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-body-secondary">Booking status</span>
                    <x-shared.status-badge :status="$ticket->hotelBooking->status" />
                </li>
            </ul>
        </div>

        <div class="card shadow-sm">
            <div class="card-header fw-semibold">How it was issued</div>
            <div class="card-body">
                @if ($ticket->issuedBy)
                    <p class="mb-0">
                        Issued at the counter by <strong>{{ $ticket->issuedBy->name }}</strong>
                        on {{ $ticket->issued_at?->format('j M Y, H:i') }}.
                    </p>
                @else
                    <p class="mb-0">
                        Purchased online by the passenger. No counter staff were involved, so
                        <code>issued_by</code> and <code>issued_at</code> are empty.
                    </p>
                @endif
            </div>
        </div>
    </div>

</div>

@endsection
