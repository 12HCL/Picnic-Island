<?php

namespace App\Http\Requests\Hotel;

use Illuminate\Foundation\Http\FormRequest;

class StoreHotelBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:visitor middleware handles access
    }

    public function rules(): array
    {
        return [
            'hotel_id'  => ['required', 'integer', 'exists:hotels,id'],
            'room_ids'  => ['required', 'array', 'min:1'],
            'room_ids.*' => ['integer', 'exists:rooms,id'],
            'check_in'  => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests'    => ['required', 'integer', 'min:1', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'check_in.after_or_equal' => 'Check-in date must be today or in the future.',
            'check_out.after'         => 'Check-out must be at least one day after check-in.',
            'room_ids.required'       => 'Please select at least one room.',
        ];
    }
}
