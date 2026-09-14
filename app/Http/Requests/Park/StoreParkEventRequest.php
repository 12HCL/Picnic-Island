<?php

namespace App\Http\Requests\Park;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Scheduling one instance of an activity. MASTER_SCHEMA.md §13.
 */
class StoreParkEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:park_staff middleware handles access
    }

    public function rules(): array
    {
        return [
            'park_activity_id' => ['required', 'integer', 'exists:park_activities,id'],
            'event_date' => ['required', 'date', 'after_or_equal:today'],
            // H:i from a time input; the column is TIME.
            'start_time' => ['required', 'date_format:H:i'],
            'capacity' => ['required', 'integer', 'min:1', 'max:65535'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'status' => ['nullable', Rule::in(['scheduled', 'cancelled', 'completed'])],
        ];
    }

    /**
     * The table has a UNIQUE index on (park_activity_id, event_date, start_time), so a
     * duplicate would otherwise surface as a raw SQLSTATE 23000 on a white screen. Caught
     * here it is a field error next to the time input.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // Compared as H:i in PHP rather than as a string in SQL. MySQL stores a TIME
            // column as 17:30:00 and SQLite keeps whatever string it was given, so a
            // literal comparison passes on one driver and silently misses on the other —
            // letting the UNIQUE index throw a raw SQLSTATE 23000 instead. At most a
            // handful of rows match one activity on one date.
            $time = $this->string('start_time')->toString();

            $exists = \App\Models\ParkEvent::where('park_activity_id', $this->integer('park_activity_id'))
                ->whereDate('event_date', $this->string('event_date')->toString())
                ->get()
                ->contains(fn ($event) => \Illuminate\Support\Carbon::parse($event->start_time)->format('H:i') === $time);

            if ($exists) {
                $validator->errors()->add(
                    'start_time',
                    'That activity is already scheduled at this time on this date.',
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'event_date.after_or_equal' => 'An event cannot be scheduled in the past.',
        ];
    }
}
