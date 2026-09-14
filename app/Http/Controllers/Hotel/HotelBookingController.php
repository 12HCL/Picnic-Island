<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hotel\StoreHotelBookingRequest;
use App\Http\Requests\Hotel\UpdateHotelBookingRequest;
use App\Models\Hotel;
use App\Models\HotelBooking;
use App\Models\HotelBookingRoom;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\Hotel\HotelBookingGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HotelBookingController extends Controller
{
    /**
     * Booking creation form.
     * GET /hotel-bookings/create → hotel.bookings.create
     * Role: visitor
     */
    public function create(Request $request): View
    {
        $hotels    = Hotel::orderBy('name')->get();
        $roomTypes = RoomType::with('hotel')->orderBy('name')->get();
        $selected  = [
            'hotel_id'  => $request->integer('hotel_id') ?: null,
            'check_in'  => $request->string('check_in')->toString() ?: null,
            'check_out' => $request->string('check_out')->toString() ?: null,
        ];

        return view('hotel.bookings.create', compact('hotels', 'roomTypes', 'selected'));
    }

    /**
     * Store a new hotel booking.
     * POST /hotel-bookings → redirect to hotel.bookings.show
     * Role: visitor
     */
    public function store(StoreHotelBookingRequest $request): RedirectResponse
    {
        $user = auth()->user();

        $rooms = Room::whereIn('id', $request->validated('room_ids'))
            ->where('hotel_id', $request->validated('hotel_id'))
            ->with('roomType')
            ->get();

        abort_if($rooms->isEmpty(), 422, 'No valid rooms found for this hotel.');

        $checkIn  = $request->validated('check_in');
        $checkOut = $request->validated('check_out');
        $nights   = (int) now()->parse($checkIn)->diffInDays(now()->parse($checkOut));

        // Verify every room is actually available — belt-and-braces on top of the JS check
        foreach ($rooms as $room) {
            abort_unless(
                $room->isAvailableFor($checkIn, $checkOut),
                409,
                "Room {$room->room_number} is not available for the selected dates."
            );
        }

        $totalAmount = $rooms->sum(fn (Room $room) => $room->roomType->base_price * $nights);

        $booking = DB::transaction(function () use ($request, $user, $rooms, $checkIn, $checkOut, $nights, $totalAmount) {
            $booking = HotelBooking::create([
                'user_id'      => $user->id,
                'hotel_id'     => $request->validated('hotel_id'),
                'reference'    => $this->nextReference(),
                'check_in'     => $checkIn,
                'check_out'    => $checkOut,
                'guests'       => $request->validated('guests'),
                'total_amount' => $totalAmount,
                'status'       => 'pending',
            ]);

            foreach ($rooms as $room) {
                HotelBookingRoom::create([
                    'hotel_booking_id' => $booking->id,
                    'room_id'          => $room->id,
                    'nightly_rate'     => $room->roomType->base_price,
                    'nights'           => $nights,
                ]);
            }

            return $booking;
        });

        return redirect()->route('hotel.bookings.show', $booking)
            ->with('success', "Booking {$booking->reference} created — awaiting hotel confirmation.");
    }

    /**
     * Booking detail — doubles as the confirmation screen.
     * GET /hotel-bookings/{booking} → hotel.bookings.show
     * Role: visitor (own booking) or hotel_staff
     *
     * Passes canBookFerry so the view can show the BR-01 CTA for Naayif's module.
     */
    public function show(HotelBooking $booking): View
    {
        $this->authoriseView($booking);

        $booking->load(['hotel', 'rooms.roomType', 'payments']);

        $payment = $booking->payments()->where('status', 'paid')->first();

        // canBookFerry: booking must be confirmed or checked_in, and check_out must be in the future.
        // We use today as a proxy sailing date — once the booking is confirmed the visitor can
        // navigate to ferry.schedules and pick their actual date. The gateway will re-check there.
        $gateway       = app(HotelBookingGateway::class);
        $canBookFerry  = $gateway->hasValidBookingFor(auth()->user(), now()->toDateString())
            && $booking->status === 'confirmed'
            && $booking->check_out >= now()->toDateString();

        return view('hotel.bookings.show', compact('booking', 'payment', 'canBookFerry'));
    }

    /**
     * Staff edit form.
     * GET /hotel-bookings/{booking}/edit → hotel.bookings.show (staff re-uses show layout)
     * Role: hotel_staff
     */
    public function edit(HotelBooking $booking): View
    {
        $booking->load(['hotel', 'rooms.roomType']);

        return view('hotel.bookings.edit', compact('booking'));
    }

    /**
     * Staff update a booking (status transitions, date corrections).
     * PUT /hotel-bookings/{booking} → redirect to show
     * Role: hotel_staff
     */
    public function update(UpdateHotelBookingRequest $request, HotelBooking $booking): RedirectResponse
    {
        $booking->update($request->validated());

        return redirect()->route('hotel.bookings.show', $booking)
            ->with('success', 'Booking updated.');
    }

    /**
     * Cancel a booking — status change only, never a delete.
     * POST /hotel-bookings/{booking}/cancel → redirect to show
     * Role: hotel_staff
     * BUILD_CONTRACT.md §3: no destroy on hotel_bookings.
     */
    public function cancel(HotelBooking $booking): RedirectResponse
    {
        abort_if(
            in_array($booking->status, ['completed', 'cancelled'], true),
            409,
            'This booking cannot be cancelled in its current state.'
        );

        $booking->update(['status' => 'cancelled']);

        return redirect()->route('hotel.bookings.show', $booking)
            ->with('success', "Booking {$booking->reference} has been cancelled.");
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Visitors may only see their own booking. Hotel staff may see any booking.
     * All other roles are denied with 403 per BUILD_CONTRACT.md §3.
     */
    private function authoriseView(HotelBooking $booking): void
    {
        $user = auth()->user();

        if ($user->hasRole('hotel_staff')) {
            return;
        }

        if ($user->hasRole('visitor') && $booking->user_id === $user->id) {
            return;
        }

        abort(403);
    }

    /**
     * Next hotel booking reference — PIB-HB-000001, PIB-HB-000002, ...
     * Called inside DB::transaction() so the lockForUpdate() holds until commit.
     */
    private function nextReference(): string
    {
        $lastId = HotelBooking::query()->lockForUpdate()->max('id') ?? 0;

        return sprintf('PIB-HB-%06d', $lastId + 1);
    }
}
