<?php

namespace App\Services\Hotel;

use App\Models\HotelBooking;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HotelBookingGateway
{
    /**
     * Bookings that authorise this user to sail on the given date.
     * Status confirmed or checked_in, and the date falls within check_in..check_out.
     * Empty collection means the visitor may not travel - BR-01.
     *
     * @return Collection<int, HotelBooking>
     */
    public function validBookingsFor(User $user, string $sailingDate): Collection
    {
        return HotelBooking::where('user_id', $user->id)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->whereDate('check_in', '<=', $sailingDate)
            ->whereDate('check_out', '>=', $sailingDate)
            ->with('hotel')
            ->get();
    }

    /**
     * True when validBookingsFor() is non-empty. Convenience for $canBookFerry.
     */
    public function hasValidBookingFor(User $user, string $sailingDate): bool
    {
        return $this->validBookingsFor($user, $sailingDate)->isNotEmpty();
    }

    /**
     * Re-check one specific booking at write time. Throws if it does not authorise travel.
     * MUST be called inside a transaction and MUST lockForUpdate() the hotel_bookings row -
     * otherwise the booking can be cancelled between the eligibility read and the insert,
     * and a ticket is issued against a booking that no longer exists.
     *
     * @throws HttpException
     */
    public function assertAuthorises(int $hotelBookingId, User $user, string $sailingDate): HotelBooking
    {
        $booking = HotelBooking::whereKey($hotelBookingId)
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->first();

        if (! $booking) {
            abort(422, 'Hotel booking not found or does not belong to the authenticated user.');
        }

        if (! in_array($booking->status, ['confirmed', 'checked_in'], true)) {
            abort(422, 'Hotel booking must be confirmed or checked in to authorise ferry travel.');
        }

        $checkIn = $booking->check_in->toDateString();
        $checkOut = $booking->check_out->toDateString();

        if ($sailingDate < $checkIn || $sailingDate > $checkOut) {
            abort(422, 'The sailing date is outside your hotel booking stay duration.');
        }

        return $booking;
    }
}
