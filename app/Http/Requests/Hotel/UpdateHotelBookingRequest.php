<?php

namespace App\Http\Requests\Hotel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHotelBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:hotel_staff middleware handles access
    }

    public function rules(): array
    {
        return [
            'check_in'  => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests'    => ['required', 'integer', 'min:1', 'max:20'],
            'status'    => ['required', Rule::in(['pending', 'confirmed', 'checked_in', 'completed', 'cancelled'])],
        ];
    }

    public function messages(): array
    {
        return [
            'check_out.after' => 'Check-out must be at least one day after check-in.',
        ];
    }
}
