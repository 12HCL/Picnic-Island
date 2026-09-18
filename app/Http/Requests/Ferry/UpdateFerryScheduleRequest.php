<?php

namespace App\Http\Requests\Ferry;

use Illuminate\Validation\Rule;

/**
 * Editing an existing sailing. UC-13.
 *
 * Same shape as StoreFerryScheduleRequest, with one deliberate difference: the departure
 * date may be in the past, because an operator must still be able to correct the record of
 * a sailing that has already run. Only NEW sailings are required to be in the future
 * (UC-13, assumption 2).
 */
class UpdateFerryScheduleRequest extends StoreFerryScheduleRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'departure_date' => ['required', 'date'],
            'status' => ['required', Rule::in(['scheduled', 'departed', 'cancelled'])],
        ]);
    }
}
