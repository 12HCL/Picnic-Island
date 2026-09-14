<?php

namespace App\Http\Requests\Park;

use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An at-entrance sale, made by park staff to a walk-up buyer.
 *
 * There is deliberately no buyer field. MASTER_SCHEMA.md §14 keeps gate sales anonymous —
 * requiring a walk-up buyer to register would collapse most of the practical difference
 * between the two channels the brief asks for. The staff member is taken from the session,
 * never from the form, so a posted user id cannot attribute a sale to someone else.
 */
class StoreGateSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:park_staff middleware handles access
    }

    public function rules(): array
    {
        return [
            'park_event_id' => ['required', 'integer', 'exists:park_events,id'],
            // A gate queue is a family, not a coach party; the online cap applies here too.
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
            'method' => ['required', Rule::in(PaymentService::METHODS)],
        ];
    }

    public function messages(): array
    {
        return [
            'park_event_id.required' => 'Choose which event this admission is for.',
        ];
    }
}
