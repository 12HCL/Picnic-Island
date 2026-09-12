<?php

namespace App\Http\Requests\Hotel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:hotel_staff middleware handles access
    }

    public function rules(): array
    {
        return [
            'hotel_id'     => ['required', 'integer', 'exists:hotels,id'],
            'room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'room_number'  => [
                'required',
                'string',
                'max:10',
                Rule::unique('rooms')->where('hotel_id', $this->input('hotel_id')),
            ],
            'floor'  => ['nullable', 'integer', 'min:0', 'max:200'],
            'status' => ['required', Rule::in(['available', 'maintenance', 'out_of_service'])],
        ];
    }

    public function messages(): array
    {
        return [
            'room_number.unique' => 'That room number already exists in this hotel.',
        ];
    }
}
