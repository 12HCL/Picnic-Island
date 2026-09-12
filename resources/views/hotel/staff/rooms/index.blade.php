@extends('layouts.app')
@section('title', 'Hotel Rooms Management')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Hotel Rooms</h1>
        <p class="text-muted small mb-0">Manage physical rooms, types, floors, and operational statuses</p>
    </div>
    <div class="btn-group">
        <a href="{{ route('hotel.staff.rooms.create') }}" class="btn btn-primary btn-sm">+ Add New Room</a>
        <a href="{{ route('hotel.dashboard') }}" class="btn btn-outline-secondary btn-sm">&larr; Dashboard</a>
    </div>
</div>

{{-- Filters --}}
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('hotel.staff.rooms.index') }}" class="row g-3">
            <div class="col-md-4">
                <label for="hotel_id" class="form-label small text-muted">Hotel</label>
                <select name="hotel_id" id="hotel_id" class="form-select form-select-sm">
                    <option value="">-- All Hotels --</option>
                    @foreach ($hotels as $h)
                        <option value="{{ $h->id }}" {{ ($filters['hotel_id'] ?? '') == $h->id ? 'selected' : '' }}>
                            {{ $h->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="room_type_id" class="form-label small text-muted">Room Type</label>
                <select name="room_type_id" id="room_type_id" class="form-select form-select-sm">
                    <option value="">-- All Room Types --</option>
                    @foreach ($roomTypes as $rt)
                        <option value="{{ $rt->id }}" {{ ($filters['room_type_id'] ?? '') == $rt->id ? 'selected' : '' }}>
                            {{ $rt->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label small text-muted">Status</label>
                <select name="status" id="status" class="form-select form-select-sm">
                    <option value="">-- All Statuses --</option>
                    @foreach (['available', 'maintenance', 'out_of_service'] as $st)
                        <option value="{{ $st }}" {{ ($filters['status'] ?? '') === $st ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $st)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                <a href="{{ route('hotel.staff.rooms.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

{{-- Rooms Table --}}
<div class="card shadow-sm">
    <div class="card-body p-0">
        @if ($rooms->isEmpty())
            <div class="p-4 text-center text-muted">No rooms match the criteria.</div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>Hotel</th>
                            <th>Room Number</th>
                            <th>Room Type</th>
                            <th>Floor</th>
                            <th>Base Price</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rooms as $room)
                            <tr>
                                <td>{{ $room->hotel->name }}</td>
                                <td><strong>Room {{ $room->room_number }}</strong></td>
                                <td>
                                    {{ $room->roomType->name }}
                                    <small class="text-muted d-block">Max {{ $room->roomType->max_occupancy }} guests</small>
                                </td>
                                <td>Floor {{ $room->floor ?? 'G' }}</td>
                                <td>MVR {{ number_format($room->roomType->base_price, 2) }}</td>
                                <td>
                                    <x-shared.status-badge :status="$room->status" />
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('hotel.staff.rooms.edit', $room) }}" class="btn btn-outline-secondary">
                                            Edit
                                        </a>
                                        <form action="{{ route('hotel.staff.rooms.destroy', $room) }}" method="POST"
                                              onsubmit="return confirm('Are you sure you want to delete Room {{ $room->room_number }}?');"
                                              class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($rooms->hasPages())
                <div class="card-footer bg-transparent py-3">
                    {{ $rooms->links() }}
                </div>
            @endif
        @endif
    </div>
</div>

@endsection
