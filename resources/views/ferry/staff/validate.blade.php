{{--
    ferry.staff.validate — boarding validation at the jetty. Module 3, Ferry.

    View-data contract, BUILD_CONTRACT.md §3: $result is null before the operator has
    searched; otherwise ticket, hotelBooking, valid and reason.
--}}
@extends('layouts.app')
@section('title', 'Validate a ferry pass')
@section('content')

{{-- Today's date is stated because a pass is refused for not being for today, and the
     operator should not have to supply that date from memory to understand the refusal. --}}
<x-shared.page-header
    title="Validate a ferry pass"
    :subtitle="'A pass is only good for today, '.now()->format('l j F Y')">
    <a href="{{ route('ferry.dashboard') }}" class="btn btn-outline-secondary">Back to operations</a>
</x-shared.page-header>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="POST" action="{{ route('ferry.staff.validate.check') }}" class="row g-2">
            @csrf
            <div class="col-md-8">
                <label for="reference" class="visually-hidden">Ticket reference</label>
                <input type="text" name="reference" id="reference"
                       class="form-control @error('reference') is-invalid @enderror"
                       placeholder="PIB-FT-000001" value="{{ old('reference') }}"
                       autofocus required>
                @error('reference')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">Check pass</button>
            </div>
        </form>
    </div>
</div>

@if ($result !== null)

    <div class="card shadow-sm border-{{ $result['valid'] ? 'success' : 'danger' }}">
        <div class="card-header bg-{{ $result['valid'] ? 'success' : 'danger' }} text-white fw-semibold">
            {{ $result['valid'] ? 'Valid — may board' : 'Not valid for boarding' }}
        </div>

        <div class="card-body">
            @unless ($result['valid'])
                <p class="mb-3">{{ $result['reason'] }}</p>
            @endunless

            @if ($result['ticket'])
                @php($ticket = $result['ticket'])

                <div class="row g-4">
                    <div class="col-md-6">
                        <h2 class="h6 text-body-secondary text-uppercase small">Pass</h2>
                        <ul class="list-unstyled mb-0">
                            <li><span class="text-body-secondary">Reference:</span>
                                <span class="font-monospace">{{ $ticket->reference }}</span></li>
                            <li><span class="text-body-secondary">Passenger:</span> {{ $ticket->user->name }}</li>
                            <li><span class="text-body-secondary">Status:</span>
                                <x-shared.status-badge :status="$ticket->status" /></li>
                            <li><span class="text-body-secondary">Sailing:</span>
                                {{ $ticket->schedule->departure_date->format('j M Y') }} at
                                {{ \Illuminate\Support\Carbon::parse($ticket->schedule->departure_time)->format('H:i') }}</li>
                            <li><span class="text-body-secondary">Vessel:</span>
                                {{ $ticket->schedule->vessel->name }}</li>
                        </ul>
                    </div>

                    <div class="col-md-6">
                        {{-- BR-01 shown at the gate. A pass cannot exist without this
                             booking, so an operator can always see what authorised it. --}}
                        <h2 class="h6 text-body-secondary text-uppercase small">Authorising hotel booking</h2>
                        @if ($result['hotelBooking'])
                            <ul class="list-unstyled mb-0">
                                <li><span class="text-body-secondary">Reference:</span>
                                    <span class="font-monospace">{{ $result['hotelBooking']->reference }}</span></li>
                                <li><span class="text-body-secondary">Hotel:</span>
                                    {{ $result['hotelBooking']->hotel->name }}</li>
                                <li><span class="text-body-secondary">Stay:</span>
                                    {{ $result['hotelBooking']->check_in->format('j M') }} to
                                    {{ $result['hotelBooking']->check_out->format('j M Y') }}</li>
                                <li><span class="text-body-secondary">Status:</span>
                                    <x-shared.status-badge :status="$result['hotelBooking']->status" /></li>
                            </ul>
                        @endif
                    </div>
                </div>

                @if ($result['valid'])
                    <form method="POST" action="{{ route('ferry.staff.validate.board', $ticket) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="btn btn-success">Mark as boarded</button>
                        <span class="form-text ms-2">
                            Recording boarding is a separate, deliberate step — a lookup never
                            changes the pass on its own.
                        </span>
                    </form>
                @endif
            @endif
        </div>
    </div>

@endif

@endsection
