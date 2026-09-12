<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    /**
     * AJAX: available rooms for a hotel + date range.
     * GET /ajax/hotel/availability?hotel_id=&check_in=&check_out=
     * Route name: ajax.hotel.availability
     * Role: auth (visitor selecting rooms on the booking form)
     *
     * Returns JSON so the booking form can re-render the room dropdown
     * whenever the visitor changes dates — BUILD_CONTRACT.md §7.
     *
     * Lives in routes/modules/hotel.php under 'auth' middleware — NOT api.php.
     * Session auth does not work through Laravel's stateless api middleware group.
     */
    public function show(Request $request): JsonResponse
    {
        $request->validate([
            'hotel_id'  => ['required', 'integer', 'exists:hotels,id'],
            'check_in'  => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
        ]);

        $checkIn  = $request->string('check_in')->toString();
        $checkOut = $request->string('check_out')->toString();

        $rooms = Room::where('hotel_id', $request->integer('hotel_id'))
            ->where('status', 'available')
            ->with('roomType')
            ->get()
            ->filter(fn (Room $room) => $room->isAvailableFor($checkIn, $checkOut))
            ->values()
            ->map(fn (Room $room) => [
                'id'           => $room->id,
                'room_number'  => $room->room_number,
                'floor'        => $room->floor,
                'type_name'    => $room->roomType->name,
                'nightly_rate' => number_format($room->roomType->base_price, 2),
                'max_occupancy' => $room->roomType->max_occupancy,
            ]);

        return response()->json(['rooms' => $rooms]);
    }
}
