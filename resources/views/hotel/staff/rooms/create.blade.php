@extends('layouts.app')
@section('title', 'Add New Room')
@section('content')

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('hotel.staff.rooms.index') }}">Rooms</a></li>
        <li class="breadcrumb-item active">Add Room</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h1 class="h5 mb-0 text-white">Add New Room</h1>
            </div>
            <div class="card-body">
                <form action="{{ route('hotel.staff.rooms.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="hotel_id" class="form-label fw-semibold">Hotel <span class="text-danger">*</span></label>
                        <select name="hotel_id" id="hotel_id" class="form-select @error('hotel_id') is-invalid @enderror" required>
                            <option value="">-- Select Hotel --</option>
                            @foreach ($hotels as $h)
                                <option value="{{ $h->id }}" {{ old('hotel_id') == $h->id ? 'selected' : '' }}>
                                    {{ $h->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('hotel_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="room_type_id" class="form-label fw-semibold">Room Type <span class="text-danger">*</span></label>
                        <select name="room_type_id" id="room_type_id" class="form-select @error('room_type_id') is-invalid @enderror" required>
                            <option value="">-- Select Room Type --</option>
                            @foreach ($roomTypes as $rt)
                                <option value="{{ $rt->id }}" {{ old('room_type_id') == $rt->id ? 'selected' : '' }}>
                                    {{ $rt->hotel ? $rt->hotel->name . ' - ' : '' }}{{ $rt->name }} (MVR {{ number_format($rt->base_price, 2) }}/night)
                                </option>
                            @endforeach
                        </select>
                        @error('room_type_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="room_number" class="form-label fw-semibold">Room Number <span class="text-danger">*</span></label>
                            <input type="text" name="room_number" id="room_number"
                                   placeholder="e.g. 101"
                                   value="{{ old('room_number') }}"
                                   class="form-control @error('room_number') is-invalid @enderror" required>
                            @error('room_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="floor" class="form-label fw-semibold">Floor</label>
                            <input type="number" name="floor" id="floor" min="0" max="200"
                                   placeholder="e.g. 1"
                                   value="{{ old('floor') }}"
                                   class="form-control @error('floor') is-invalid @enderror">
                            @error('floor')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="status" class="form-label fw-semibold">Operational Status <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                            @foreach (['available', 'maintenance', 'out_of_service'] as $st)
                                <option value="{{ $st }}" {{ old('status', 'available') === $st ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $st)) }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('hotel.staff.rooms.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">Create Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
