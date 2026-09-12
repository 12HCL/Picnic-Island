@extends('layouts.app')
@section('title', 'Booking ' . $booking->reference)
@section('content')

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        @if(auth()->user()->hasRole('hotel_staff'))
            <li class="breadcrumb-item"><a href="{{ route('hotel.staff.bookings.index') }}">Bookings</a></li>
        @else
            <li class="breadcrumb-item"><a href="{{ route('hotel.index') }}">Hotels</a></li>
        @endif
        <li class="breadcrumb-item active">{{ $booking->reference }}</li>
    </ol>
</nav>

<div class="row g-4">
    <div class="col-lg-8">
        {{-- Booking Summary Card --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h5 mb-0 d-inline-block me-2">Booking {{ $booking->reference }}</h1>
                    <x-shared.status-badge :status="$booking->status" />
                </div>
                <span class="text-muted small">Created: {{ $booking->created_at->format('d M Y, H:i') }}</span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-sm-6">
                        <p class="text-muted small mb-1">Hotel</p>
                        <h5 class="mb-1">{{ $booking->hotel->name }}</h5>
                        <p class="text-secondary small mb-0">{{ $booking->hotel->address }}</p>
                    </div>
                    <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                        <p class="text-muted small mb-1">Guest</p>
                        <h6 class="mb-0">{{ $booking->user->name }}</h6>
                        <p class="text-secondary small mb-0">{{ $booking->user->email }}</p>
                        @if($booking->user->phone)
                            <p class="text-secondary small mb-0">{{ $booking->user->phone }}</p>
                        @endif
                    </div>
                </div>

                <hr>

                <div class="row g-3 text-center py-2">
                    <div class="col-4">
                        <div class="p-2 border rounded bg-light">
                            <span class="small text-muted d-block">Check-in</span>
                            <strong>{{ $booking->check_in->format('d M Y') }}</strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 border rounded bg-light">
                            <span class="small text-muted d-block">Check-out</span>
                            <strong>{{ $booking->check_out->format('d M Y') }}</strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 border rounded bg-light">
                            <span class="small text-muted d-block">Guests</span>
                            <strong>{{ $booking->guests }} {{ Str::plural('Guest', $booking->guests) }}</strong>
                        </div>
                    </div>
                </div>

                <h5 class="h6 mt-4 mb-3">Reserved Rooms</h5>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Room</th>
                                <th>Type</th>
                                <th>Nights</th>
                                <th class="text-end">Nightly Rate</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($booking->rooms as $room)
                                @php
                                    $subtotal = $room->pivot->nightly_rate * $room->pivot->nights;
                                @endphp
                                <tr>
                                    <td><strong>Room {{ $room->room_number }}</strong> (Floor {{ $room->floor ?? 'G' }})</td>
                                    <td>{{ $room->roomType->name }}</td>
                                    <td>{{ $room->pivot->nights }}</td>
                                    <td class="text-end">MVR {{ number_format($room->pivot->nightly_rate, 2) }}</td>
                                    <td class="text-end fw-semibold">MVR {{ number_format($subtotal, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="4" class="text-end">Total Amount:</th>
                                <th class="text-end text-primary fs-5">MVR {{ number_format($booking->total_amount, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- BR-01 Hand-off CTA: Can book ferry --}}
        @if ($canBookFerry)
            <div class="alert alert-success d-flex align-items-center justify-content-between flex-wrap gap-2 shadow-sm">
                <div>
                    <h5 class="alert-heading h6 mb-1">🚢 Ferry Booking Unlocked!</h5>
                    <p class="mb-0 small">
                        Because you hold a confirmed hotel booking, you are now authorized to purchase ferry tickets to Picnic Island.
                    </p>
                </div>
                <div>
                    @if (Route::has('ferry.schedules.index'))
                        <a href="{{ route('ferry.schedules.index') }}" class="btn btn-success btn-sm">
                            Book Ferry Crossing &rarr;
                        </a>
                    @else
                        <a href="{{ url('/ferry/schedules') }}" class="btn btn-success btn-sm">
                            Book Ferry Crossing &rarr;
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Sidebar: Payment & Actions --}}
    <div class="col-lg-4">
        {{-- Payment Status Box --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-body-tertiary">
                <h5 class="h6 mb-0">Payment Status</h5>
            </div>
            <div class="card-body">
                @if ($payment)
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge text-bg-success me-2">Paid</span>
                        <span class="fw-semibold">MVR {{ number_format($payment->amount, 2) }}</span>
                    </div>
                    <ul class="list-unstyled small text-muted mb-0">
                        <li><strong>Ref:</strong> {{ $payment->reference }}</li>
                        <li><strong>Method:</strong> {{ ucfirst($payment->method) }}</li>
                        <li><strong>Paid at:</strong> {{ $payment->paid_at ? \Carbon\Carbon::parse($payment->paid_at)->format('d M Y, H:i') : $payment->created_at->format('d M Y, H:i') }}</li>
                    </ul>
                @else
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge text-bg-warning me-2">Unpaid</span>
                        <span class="fw-semibold text-danger">MVR {{ number_format($booking->total_amount, 2) }} Due</span>
                    </div>

                    @if ($booking->status !== 'cancelled')
                        @if (auth()->id() === $booking->user_id)
                            <a href="{{ route('hotel.bookings.pay', $booking) }}" class="btn btn-primary w-100 mb-2">
                                Pay Now (Simulated)
                            </a>
                        @endif
                    @else
                        <p class="text-muted small mb-0">This booking has been cancelled.</p>
                    @endif
                @endif
            </div>
        </div>

        {{-- Staff Management Actions --}}
        @if (auth()->user()->hasRole('hotel_staff'))
            <div class="card shadow-sm border-warning">
                <div class="card-header bg-warning-subtle text-warning-emphasis">
                    <h5 class="h6 mb-0">Staff Controls</h5>
                </div>
                <div class="card-body d-flex flex-column gap-2">
                    @if ($booking->status === 'pending')
                        <form action="{{ route('hotel.staff.bookings.confirm', $booking) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success w-100 btn-sm">
                                Confirm Booking
                            </button>
                        </form>
                    @endif

                    @if ($booking->status === 'confirmed')
                        <form action="{{ route('hotel.staff.bookings.check-in', $booking) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100 btn-sm">
                                Check In Guest
                            </button>
                        </form>
                    @endif

                    @if ($booking->status === 'checked_in')
                        <form action="{{ route('hotel.staff.bookings.check-out', $booking) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-info w-100 btn-sm">
                                Check Out Guest (Complete)
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('hotel.bookings.edit', $booking) }}" class="btn btn-outline-secondary w-100 btn-sm">
                        Edit Booking Details
                    </a>

                    @if (!in_array($booking->status, ['completed', 'cancelled']))
                        <form action="{{ route('hotel.staff.bookings.cancel', $booking) }}" method="POST"
                              onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger w-100 btn-sm">
                                Cancel Booking
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

@endsection
