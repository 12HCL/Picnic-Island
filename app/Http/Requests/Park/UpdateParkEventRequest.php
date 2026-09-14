<?php

namespace App\Http\Requests\Park;

use App\Models\ParkEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Editing a scheduled event. Two rules apply here that do not apply on create, both of
 * them about tickets that have already been sold.
 */
class UpdateParkEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:park_staff middleware handles access
    }

    public function rules(): array
    {
        return [
            'park_activity_id' => ['required', 'integer', 'exists:park_activities,id'],
            // No after_or_equal:today here — a past event still has to be editable, to mark
            // it completed or to correct a typo after the fact.
            'event_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'capacity' => ['required', 'integer', 'min:1', 'max:65535'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'status' => ['required', Rule::in(['scheduled', 'cancelled', 'completed'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var ParkEvent $event */
            $event = $this->route('event');

            // Capacity may not fall below what is already sold. Allowing it would put the
            // event into a state BR-06 says cannot exist — seats_taken above capacity — and
            // the oversell would already have happened, with no way to un-sell it.
            if ($this->integer('capacity') < $event->seats_taken) {
                $validator->errors()->add(
                    'capacity',
                    "{$event->seats_taken} admission(s) are already sold, so capacity cannot go below that.",
                );
            }

            // The UNIQUE index again, ignoring this row. Compared as H:i in PHP for the
            // reason set out in StoreParkEventRequest — the stored format differs by driver.
            $time = $this->string('start_time')->toString();

            $clash = ParkEvent::where('park_activity_id', $this->integer('park_activity_id'))
                ->whereDate('event_date', $this->string('event_date')->toString())
                ->whereKeyNot($event->getKey())
                ->get()
                ->contains(fn ($other) => \Illuminate\Support\Carbon::parse($other->start_time)->format('H:i') === $time);

            if ($clash) {
                $validator->errors()->add(
                    'start_time',
                    'That activity is already scheduled at this time on this date.',
                );
            }
        });
    }
}
