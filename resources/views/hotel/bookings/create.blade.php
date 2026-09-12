@extends('layouts.app')
@section('title', 'Book a Hotel Stay')
@section('content')

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h1 class="h5 mb-0 text-white">Book a Hotel Stay</h1>
            </div>
            <div class="card-body">
                <form action="{{ route('hotel.bookings.store') }}" method="POST" id="bookingForm">
                    @csrf

                    {{-- Hotel selection --}}
                    <div class="mb-3">
                        <label for="hotel_id" class="form-label fw-semibold">Select Hotel <span class="text-danger">*</span></label>
                        <select name="hotel_id" id="hotel_id" class="form-select @error('hotel_id') is-invalid @enderror" required>
                            <option value="">-- Choose a Hotel --</option>
                            @foreach ($hotels as $hotel)
                                <option value="{{ $hotel->id }}" {{ (old('hotel_id', $selected['hotel_id']) == $hotel->id) ? 'selected' : '' }}>
                                    {{ $hotel->name }} ({{ $hotel->star_rating }}★) - {{ $hotel->address }}
                                </option>
                            @endforeach
                        </select>
                        @error('hotel_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Dates --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="check_in" class="form-label fw-semibold">Check-in Date <span class="text-danger">*</span></label>
                            <input type="date" name="check_in" id="check_in"
                                   min="{{ date('Y-m-d') }}"
                                   value="{{ old('check_in', $selected['check_in']) }}"
                                   class="form-control @error('check_in') is-invalid @enderror" required>
                            @error('check_in')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="check_out" class="form-label fw-semibold">Check-out Date <span class="text-danger">*</span></label>
                            <input type="date" name="check_out" id="check_out"
                                   min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                                   value="{{ old('check_out', $selected['check_out']) }}"
                                   class="form-control @error('check_out') is-invalid @enderror" required>
                            @error('check_out')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Guests --}}
                    <div class="mb-4">
                        <label for="guests" class="form-label fw-semibold">Number of Guests <span class="text-danger">*</span></label>
                        <input type="number" name="guests" id="guests" min="1" max="20"
                               value="{{ old('guests', 1) }}"
                               class="form-control @error('guests') is-invalid @enderror" required>
                        @error('guests')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Available Rooms section (Dynamic AJAX fetching per BUILD_CONTRACT.md §7) --}}
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0">Select Room(s) <span class="text-danger">*</span></label>
                            <span id="availabilityStatus" class="small text-muted">Select hotel and dates to see rooms</span>
                        </div>

                        <div id="roomsContainer" class="border rounded p-3 bg-light">
                            <p class="text-muted text-center my-3" id="noRoomsPrompt">
                                Please select a hotel and date range above to check available rooms.
                            </p>
                            <div id="roomsList" class="row g-2 d-none"></div>
                        </div>
                        @error('room_ids')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('hotel.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4" id="submitBtn">
                            Submit Booking Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const hotelSelect = document.getElementById('hotel_id');
    const checkInInput = document.getElementById('check_in');
    const checkOutInput = document.getElementById('check_out');
    const roomsList = document.getElementById('roomsList');
    const noRoomsPrompt = document.getElementById('noRoomsPrompt');
    const availabilityStatus = document.getElementById('availabilityStatus');

    async function loadAvailableRooms() {
        const hotelId = hotelSelect.value;
        const checkIn = checkInInput.value;
        const checkOut = checkOutInput.value;

        if (!hotelId || !checkIn || !checkOut) {
            roomsList.classList.add('d-none');
            noRoomsPrompt.classList.remove('d-none');
            noRoomsPrompt.textContent = 'Please select a hotel and date range above to check available rooms.';
            availabilityStatus.textContent = '';
            return;
        }

        if (checkOut <= checkIn) {
            roomsList.classList.add('d-none');
            noRoomsPrompt.classList.remove('d-none');
            noRoomsPrompt.textContent = 'Check-out date must be after check-in date.';
            availabilityStatus.textContent = 'Invalid dates';
            return;
        }

        availabilityStatus.textContent = 'Checking availability...';

        try {
            const params = new URLSearchParams({ hotel_id: hotelId, check_in: checkIn, check_out: checkOut });
            const res = await fetch(`{{ route('ajax.hotel.availability') }}?${params}`);
            if (!res.ok) throw new Error('Failed to fetch rooms');
            const data = await res.json();

            roomsList.innerHTML = '';

            if (!data.rooms || data.rooms.length === 0) {
                roomsList.classList.add('d-none');
                noRoomsPrompt.classList.remove('d-none');
                noRoomsPrompt.textContent = 'No rooms available for the selected dates. Please try different dates.';
                availabilityStatus.textContent = '0 rooms available';
                return;
            }

            noRoomsPrompt.classList.add('d-none');
            roomsList.classList.remove('d-none');
            availabilityStatus.textContent = `${data.rooms.length} room(s) available`;

            data.rooms.forEach(room => {
                const col = document.createElement('div');
                col.className = 'col-md-6';
                col.innerHTML = `
                    <div class="card h-100 border p-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="room_ids[]" value="${room.id}" id="room_${room.id}">
                            <label class="form-check-label w-100" for="room_${room.id}">
                                <div class="d-flex justify-content-between">
                                    <strong>Room ${room.room_number}</strong>
                                    <span class="badge text-bg-primary">MVR ${room.nightly_rate}/nt</span>
                                </div>
                                <div class="small text-muted">
                                    ${room.type_name} &bull; Floor ${room.floor ?? 'G'} &bull; Max ${room.max_occupancy} guests
                                </div>
                            </label>
                        </div>
                    </div>
                `;
                roomsList.appendChild(col);
            });
        } catch (err) {
            console.error(err);
            availabilityStatus.textContent = 'Error checking rooms';
        }
    }

    hotelSelect.addEventListener('change', loadAvailableRooms);
    checkInInput.addEventListener('change', loadAvailableRooms);
    checkOutInput.addEventListener('change', loadAvailableRooms);

    // Initial check if values are prepopulated
    if (hotelSelect.value && checkInInput.value && checkOutInput.value) {
        loadAvailableRooms();
    }
});
</script>

@endsection
