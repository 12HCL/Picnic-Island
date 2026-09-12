<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\HotelBooking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffBookingController extends Controller
{
    /**
     * Paginated booking list with status/date filters.
     * GET /staff/hotel/bookings → hotel.staff.bookings.index
     * Role: hotel_staff
     */
    public function index(Request $request): View
    {
        $query = HotelBooking::with(['user', 'hotel'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('date')) {
            $query->whereDate('check_in', $request->string('date'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        $bookings = $query->paginate(15)->withQueryString();

        $filters = $request->only(['status', 'date', 'search']);

        return view('hotel.staff.bookings.index', compact('bookings', 'filters'));
    }

    /**
     * Confirm a pending booking.
     * POST /staff/hotel/bookings/{booking}/confirm → redirect back
     * Role: hotel_staff
     */
    public function confirm(HotelBooking $booking): RedirectResponse
    {
        abort_unless($booking->status === 'pending', 409, 'Only pending bookings can be confirmed.');

        $booking->update(['status' => 'confirmed']);

        return redirect()->route('hotel.bookings.show', $booking)
            ->with('success', "Booking {$booking->reference} confirmed.");
    }

    /**
     * Mark a confirmed booking as checked in.
     * POST /staff/hotel/bookings/{booking}/check-in → redirect back
     * Role: hotel_staff
     */
    public function checkIn(HotelBooking $booking): RedirectResponse
    {
        abort_unless($booking->status === 'confirmed', 409, 'Only confirmed bookings can be checked in.');

        $booking->update(['status' => 'checked_in']);

        return redirect()->route('hotel.bookings.show', $booking)
            ->with('success', "Guest checked in for booking {$booking->reference}.");
    }

    /**
     * Mark a checked-in booking as completed.
     * POST /staff/hotel/bookings/{booking}/check-out → redirect back
     * Role: hotel_staff
     */
    public function checkOut(HotelBooking $booking): RedirectResponse
    {
        abort_unless($booking->status === 'checked_in', 409, 'Only checked-in bookings can be checked out.');

        $booking->update(['status' => 'completed']);

        return redirect()->route('hotel.bookings.show', $booking)
            ->with('success', "Guest checked out. Booking {$booking->reference} completed.");
    }

    /**
     * Cancel a booking (staff-initiated).
     * POST /staff/hotel/bookings/{booking}/cancel → redirect back
     * Role: hotel_staff
     */
    public function cancel(HotelBooking $booking): RedirectResponse
    {
        abort_if(
            in_array($booking->status, ['completed', 'cancelled'], true),
            409,
            'This booking cannot be cancelled in its current state.'
        );

        $booking->update(['status' => 'cancelled']);

        return redirect()->route('hotel.staff.bookings.index')
            ->with('success', "Booking {$booking->reference} cancelled.");
    }
}
