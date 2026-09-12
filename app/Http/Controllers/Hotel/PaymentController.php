<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hotel\StoreHotelPaymentRequest;
use App\Models\HotelBooking;
use App\Services\Payment\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    /**
     * Simulated payment screen.
     * GET /hotel-bookings/{booking}/pay → hotel.bookings.pay
     * Role: visitor (own booking only)
     */
    public function create(HotelBooking $booking): View
    {
        $this->authoriseOwner($booking);

        abort_if(
            $booking->payments()->where('status', 'paid')->exists(),
            409,
            'This booking has already been paid for.'
        );

        $booking->load('hotel');

        return view('hotel.bookings.pay', [
            'payable' => $booking,
            'amount'  => $booking->total_amount,
            'methods' => PaymentService::METHODS,
        ]);
    }

    /**
     * Process (simulate) the payment.
     * POST /hotel-bookings/{booking}/pay → redirect to hotel.bookings.show
     * Role: visitor (own booking only)
     */
    public function store(StoreHotelPaymentRequest $request, HotelBooking $booking): RedirectResponse
    {
        $this->authoriseOwner($booking);

        try {
            $this->payments->payForHotelBooking($booking, $request->validated('method'));
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('hotel.bookings.show', $booking)
            ->with('success', 'Payment confirmed. Your booking is now paid.');
    }

    private function authoriseOwner(HotelBooking $booking): void
    {
        if ($booking->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
