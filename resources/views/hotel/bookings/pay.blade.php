@extends('layouts.app')
@section('title', 'Pay for Hotel Booking ' . $payable->reference)
@section('content')

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('hotel.index') }}">Hotels</a></li>
        <li class="breadcrumb-item"><a href="{{ route('hotel.bookings.show', $payable) }}">{{ $payable->reference }}</a></li>
        <li class="breadcrumb-item active">Payment</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h1 class="h5 mb-0 text-white">Confirm Booking Payment</h1>
            </div>
            <div class="card-body">
                <div class="alert alert-info small mb-4">
                    <strong>Simulated Payment:</strong> No real bank transactions or card charges occur. Selecting a method and confirming will mark this booking as paid in the system.
                </div>

                <div class="mb-3 border-bottom pb-3">
                    <p class="text-muted small mb-1">Booking Reference</p>
                    <h6 class="mb-1">{{ $payable->reference }}</h6>
                    <p class="small text-secondary mb-0">{{ $payable->hotel->name }} &bull; {{ $payable->check_in->format('d M Y') }} &ndash; {{ $payable->check_out->format('d M Y') }}</p>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <span class="fs-5 text-muted">Amount Due:</span>
                    <span class="fs-3 fw-bold text-primary">MVR {{ number_format($amount, 2) }}</span>
                </div>

                <form action="{{ route('hotel.bookings.pay.store', $payable) }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label for="method" class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                        <select name="method" id="method" class="form-select @error('method') is-invalid @enderror" required>
                            <option value="">-- Choose Payment Method --</option>
                            @foreach ($methods as $method)
                                <option value="{{ $method }}" {{ old('method', 'card') === $method ? 'selected' : '' }}>
                                    {{ ucfirst($method) }} Payment
                                </option>
                            @endforeach
                        </select>
                        @error('method')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg">
                            Confirm Payment (MVR {{ number_format($amount, 2) }})
                        </button>
                        <a href="{{ route('hotel.bookings.show', $payable) }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
