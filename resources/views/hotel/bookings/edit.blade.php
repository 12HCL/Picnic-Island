@extends('layouts.app')
@section('title', 'Edit Booking ' . $booking->reference)
@section('content')

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('hotel.staff.bookings.index') }}">Bookings</a></li>
        <li class="breadcrumb-item"><a href="{{ route('hotel.bookings.show', $booking) }}">{{ $booking->reference }}</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h1 class="h5 mb-0 text-white">Edit Booking {{ $booking->reference }}</h1>
            </div>
            <div class="card-body">
                <form action="{{ route('hotel.bookings.update', $booking) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label text-muted small">Hotel</label>
                        <input type="text" class="form-control" value="{{ $booking->hotel->name }}" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small">Guest</label>
                        <input type="text" class="form-control" value="{{ $booking->user->name }} ({{ $booking->user->email }})" disabled>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="check_in" class="form-label fw-semibold">Check-in Date <span class="text-danger">*</span></label>
                            <input type="date" name="check_in" id="check_in"
                                   value="{{ old('check_in', $booking->check_in->toDateString()) }}"
                                   class="form-control @error('check_in') is-invalid @enderror" required>
                            @error('check_in')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="check_out" class="form-label fw-semibold">Check-out Date <span class="text-danger">*</span></label>
                            <input type="date" name="check_out" id="check_out"
                                   value="{{ old('check_out', $booking->check_out->toDateString()) }}"
                                   class="form-control @error('check_out') is-invalid @enderror" required>
                            @error('check_out')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="guests" class="form-label fw-semibold">Guests <span class="text-danger">*</span></label>
                            <input type="number" name="guests" id="guests" min="1" max="20"
                                   value="{{ old('guests', $booking->guests) }}"
                                   class="form-control @error('guests') is-invalid @enderror" required>
                            @error('guests')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label fw-semibold">Booking Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                @foreach (['pending', 'confirmed', 'checked_in', 'completed', 'cancelled'] as $st)
                                    <option value="{{ $st }}" {{ old('status', $booking->status) === $st ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $st)) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('hotel.bookings.show', $booking) }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">Update Booking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
