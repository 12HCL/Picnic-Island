<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hotel\StoreRoomRequest;
use App\Http\Requests\Hotel\UpdateRoomRequest;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    /**
     * Room listing with hotel/type filters.
     * GET /staff/hotel/rooms → hotel.staff.rooms.index
     * Role: hotel_staff
     */
    public function index(Request $request): View
    {
        $query = Room::with(['hotel', 'roomType'])->orderBy('room_number');

        if ($request->filled('hotel_id')) {
            $query->where('hotel_id', $request->integer('hotel_id'));
        }

        if ($request->filled('room_type_id')) {
            $query->where('room_type_id', $request->integer('room_type_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $rooms     = $query->paginate(20)->withQueryString();
        $hotels    = Hotel::orderBy('name')->get();
        $roomTypes = RoomType::orderBy('name')->get();
        $filters   = $request->only(['hotel_id', 'room_type_id', 'status']);

        return view('hotel.staff.rooms.index', compact('rooms', 'hotels', 'roomTypes', 'filters'));
    }

    /**
     * New room form.
     * GET /staff/hotel/rooms/create → hotel.staff.rooms.create
     */
    public function create(): View
    {
        $hotels    = Hotel::orderBy('name')->get();
        $roomTypes = RoomType::with('hotel')->orderBy('name')->get();

        return view('hotel.staff.rooms.create', compact('hotels', 'roomTypes'));
    }

    /**
     * Store a new room.
     * POST /staff/hotel/rooms
     */
    public function store(StoreRoomRequest $request): RedirectResponse
    {
        Room::create($request->validated());

        return redirect()->route('hotel.staff.rooms.index')
            ->with('success', 'Room added successfully.');
    }

    /**
     * Show room details and recent bookings.
     * GET /staff/hotel/rooms/{room} → hotel.staff.rooms.show
     */
    public function show(Room $room): View
    {
        $room->load(['hotel', 'roomType', 'bookings.user']);

        return view('hotel.staff.rooms.show', compact('room'));
    }

    /**
     * Edit a room.
     * GET /staff/hotel/rooms/{room}/edit → hotel.staff.rooms.edit
     */
    public function edit(Room $room): View
    {
        $hotels    = Hotel::orderBy('name')->get();
        $roomTypes = RoomType::with('hotel')->orderBy('name')->get();

        return view('hotel.staff.rooms.edit', compact('room', 'hotels', 'roomTypes'));
    }

    /**
     * Update a room.
     * PUT /staff/hotel/rooms/{room}
     */
    public function update(UpdateRoomRequest $request, Room $room): RedirectResponse
    {
        $room->update($request->validated());

        return redirect()->route('hotel.staff.rooms.index')
            ->with('success', 'Room updated.');
    }

    /**
     * Delete a room.
     * DELETE /staff/hotel/rooms/{room}
     * Rooms may be deleted — the DB uses restrictOnDelete on hotel_booking_rooms,
     * so MySQL will refuse if any booking references this room, keeping the audit trail.
     */
    public function destroy(Room $room): RedirectResponse
    {
        try {
            $room->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', 'Cannot delete this room — it has existing bookings.');
        }

        return redirect()->route('hotel.staff.rooms.index')
            ->with('success', 'Room deleted.');
    }
}
