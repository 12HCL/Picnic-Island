@extends('layouts.app')

@section('title', 'My bookings')

@section('content')
    <x-shared.page-header
        title="My bookings"
        subtitle="Your hotel stays and ferry crossings in one place."
    />

    {{-- Park tickets join this dashboard when Malaaz's Ticket model and tickets table land. --}}
    @if ($hotelBookings->isEmpty() && $ferryTickets->isEmpty())
        <div class="card shadow-sm">
            <x-shared.empty-state message="You do not have any hotel bookings or ferry tickets yet.">
                <a href="{{ route('hotel.index') }}" class="btn btn-sm btn-primary">Browse hotels</a>
            </x-shared.empty-state>
        </div>
    @else
        <div class="row g-4">
            <div class="col-12">
                <section class="card shadow-sm" aria-labelledby="hotel-bookings-heading">
                    <div class="card-header bg-body-tertiary">
                        <h2 id="hotel-bookings-heading" class="h5 mb-0">Hotel bookings</h2>
                    </div>

                    @if ($hotelBookings->isEmpty())
                        <x-shared.empty-state message="You do not have any hotel bookings yet." />
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th>Reference</th>
                                        <th>Hotel</th>
                                        <th>Stay dates</th>
                                        <th class="text-center">Guests</th>
                                        <th class="text-end">Total</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($hotelBookings as $booking)
                                        <tr>
                                            <td class="fw-semibold">{{ $booking->reference }}</td>
                                            <td>{{ $booking->hotel->name }}</td>
                                            <td class="small">
                                                {{ $booking->check_in->format('d M Y') }} &ndash;
                                                {{ $booking->check_out->format('d M Y') }}
                                            </td>
                                            <td class="text-center">{{ $booking->guests }}</td>
                                            <td class="text-end">MVR {{ number_format($booking->total_amount, 2) }}</td>
                                            <td><x-shared.status-badge :status="$booking->status" /></td>
                                            <td class="text-end">
                                                <a href="{{ route('hotel.bookings.show', $booking) }}"
                                                   class="btn btn-sm btn-outline-primary">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            </div>

            <div class="col-12">
                <section class="card shadow-sm" aria-labelledby="ferry-tickets-heading">
                    <div class="card-header bg-body-tertiary">
                        <h2 id="ferry-tickets-heading" class="h5 mb-0">Ferry tickets</h2>
                    </div>

                    @if ($ferryTickets->isEmpty())
                        <x-shared.empty-state message="You do not have any ferry tickets yet." />
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th>Reference</th>
                                        <th>Crossing</th>
                                        <th>Departure</th>
                                        <th>Vessel</th>
                                        <th class="text-end">Fare</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($ferryTickets as $ticket)
                                        <tr>
                                            <td class="fw-semibold">{{ $ticket->reference }}</td>
                                            <td>
                                                {{ $ticket->schedule->route->origin }} &rarr;
                                                {{ $ticket->schedule->route->destination }}
                                            </td>
                                            <td class="small">
                                                {{ $ticket->schedule->departure_date->format('d M Y') }}
                                                at {{ \Carbon\Carbon::parse($ticket->schedule->departure_time)->format('H:i') }}
                                            </td>
                                            <td>{{ $ticket->schedule->vessel->name }}</td>
                                            <td class="text-end">MVR {{ number_format($ticket->fare, 2) }}</td>
                                            <td><x-shared.status-badge :status="$ticket->status" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            </div>
        </div>
    @endif
@endsection
