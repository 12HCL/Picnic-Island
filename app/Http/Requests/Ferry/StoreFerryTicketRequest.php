<?php

namespace App\Http\Requests\Ferry;

use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An online ferry ticket purchase. The visitor is the passenger; role:visitor middleware
 * handles access.
 *
 * This is the third layer of BR-01, after the NOT NULL column and the gateway. It asserts
 * that a hotel booking was named at all and that it belongs to the authenticated user — a
 * cheap check that rejects a hand-posted form naming someone else's booking before the
 * request reaches the service.
 *
 * Note what is NOT validated here: whether that booking actually authorises travel on the
 * sailing date, and whether a seat is free. A form request runs outside the transaction, so
 * anything it checks can be stale by the time the row is written. BR-01's real enforcement
 * and BR-02 both belong in FerryTicketIssueService under row locks, and nowhere else.
 */
class StoreFerryTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:visitor middleware handles access
    }

    public function rules(): array
    {
        return [
            'ferry_schedule_id' => ['required', 'integer', 'exists:ferry_schedules,id'],
            'hotel_booking_id' => [
                'required',
                'integer',
                // Ownership, checked in the query rather than after the fact: a booking id
                // belonging to another visitor fails validation rather than reaching the
                // gateway.
                Rule::exists('hotel_bookings', 'id')->where(
                    fn ($query) => $query->where('user_id', $this->user()->id),
                ),
            ],
            'method' => ['required', Rule::in(PaymentService::METHODS)],
        ];
    }

    public function messages(): array
    {
        return [
            'ferry_schedule_id.exists' => 'That sailing no longer exists.',
            'hotel_booking_id.required' => 'A ferry ticket must name the hotel booking that authorises it.',
            'hotel_booking_id.exists' => 'That hotel booking does not belong to you.',
        ];
    }
}
