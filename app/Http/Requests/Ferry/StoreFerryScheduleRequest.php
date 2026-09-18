<?php

namespace App\Http\Requests\Ferry;

use App\Models\FerrySchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Scheduling a sailing. UC-13, main flow steps 3-4.
 *
 * Assumption 2 of UC-13: a new sailing must depart in the future. Enforced here rather than
 * in the controller, because it is a property of the input, not a race.
 *
 * The vessel double-booking check (UC-13 E2) is NOT here. It compares against other rows and
 * is therefore a check-then-act, so it belongs in the controller where it can be made under
 * the same conditions as the write - the same reasoning that keeps BR-02 out of
 * StoreFerryTicketRequest.
 */
class StoreFerryScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:ferry_operator middleware handles access
    }

    public function rules(): array
    {
        return [
            'ferry_route_id' => ['required', 'integer', 'exists:ferry_routes,id'],
            // A vessel under maintenance or retired cannot be assigned to a new sailing.
            'vessel_id' => [
                'required',
                'integer',
                Rule::exists('vessels', 'id')->where(fn ($query) => $query->where('status', 'active')),
            ],
            'departure_date' => ['required', 'date', 'after_or_equal:today'],
            'departure_time' => ['required', 'date_format:H:i'],
            'status' => ['nullable', Rule::in(['scheduled', 'departed', 'cancelled'])],
        ];
    }

    public function messages(): array
    {
        return [
            'vessel_id.exists' => 'That vessel is not active, so it cannot be assigned to a sailing.',
            'departure_date.after_or_equal' => 'A sailing cannot be scheduled in the past.',
            'departure_time.date_format' => 'Enter the departure time as HH:MM, for example 09:30.',
        ];
    }

    /**
     * The UNIQUE composite on (ferry_route_id, departure_date, departure_time) would
     * otherwise surface as a raw 1062 integrity error. Caught here so the operator gets a
     * sentence instead — the same route cannot be scheduled twice at one moment.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $clash = FerrySchedule::query()
                ->where('ferry_route_id', $this->integer('ferry_route_id'))
                ->whereDate('departure_date', $this->date('departure_date'))
                ->where('departure_time', $this->string('departure_time').':00')
                ->when($this->route('schedule'), fn ($query, $schedule) => $query->whereKeyNot($schedule->id ?? $schedule))
                ->exists();

            if ($clash) {
                $validator->errors()->add(
                    'departure_time',
                    'That route is already scheduled at this date and time.',
                );
            }
        });
    }
}
