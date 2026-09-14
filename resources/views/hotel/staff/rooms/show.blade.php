@extends('layouts.app')
@section('title', 'Room ' . $room->room_number . ' Details')
@section('content')

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('hotel.staff.rooms.index') }}">Rooms</a></li>
        <li class="breadcrumb-item active">Room {{ $room->room_number }}</li>
    </ol>
</nav>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h1 class="h5 mb-0 text-white">Room {{ $room->room_number }}</h1>
                <x-shared.status-badge :status="$room->status" />
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Hotel</dt>
                    <dd class="col-sm-8 fw-semibold">{{ $room->hotel->name }}</dd>

                    <dt class="col-sm-4 text-muted">Room Type</dt>
                    <dd class="col-sm-8">{{ $room->roomType->name }}</dd>

                    <dt class="col-sm-4 text-muted">Base Price</dt>
                    <dd class="col-sm-8">MVR {{ number_format($room->roomType->base_price, 2) }} / night</dd>

                    <dt class="col-sm-4 text-muted">Capacity</dt>
                    <dd class="col-sm-8">Max {{ $room->roomType->max_occupancy }} guests</dd>

                    <dt class="col-sm-4 text-muted">Floor</dt>
                    <dd class="col-sm-8">Floor {{ $room->floor ?? 'Ground' }}</dd>

                    <dt class="col-sm-4 text-muted">Physical Status</dt>
                    <dd class="col-sm-8">{{ ucfirst(str_replace('_', ' ', $room->status)) }}</dd>
                </dl>
            </div>
            <div class="card-footer bg-white d-flex gap-2">
                <a href="{{ route('hotel.staff.rooms.edit', $room) }}" class="btn btn-primary btn-sm">
                    Edit Room
                </a>
                <a href="{{ route('hotel.staff.rooms.index') }}" class="btn btn-outline-secondary btn-sm">
                    Back to Rooms
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h2 class="h6 mb-0 fw-bold">Recent Bookings for this Room</h2>
            </div>
            <div class="card-body p-0">
                @if ($room->bookings->isEmpty())
                    <div class="p-4 text-center text-muted">
                        No bookings recorded for this room yet.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Reference</th>
                                    <th>Guest</th>
                                    <th>Dates</th>
                                    <th>Status</th>
                                    <th class="text-end">View</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($room->bookings as $booking)
                                    <tr>
                                        <td>
                                            <a href="{{ route('hotel.bookings.show', $booking) }}" class="fw-semibold text-decoration-none">
                                                {{ $booking->reference }}
                                            </a>
                                        </td>
                                        <td>{{ $booking->user->name }}</td>
                                        <td>
                                            <small class="d-block">{{ $booking->check_in->format('d M Y') }}</small>
                                            <small class="text-muted">to {{ $booking->check_out->format('d M Y') }}</small>
                                        </td>
                                        <td>
                                            <x-shared.status-badge :status="$booking->status" />
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('hotel.bookings.show', $booking) }}" class="btn btn-outline-primary btn-sm">
                                                Details
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
