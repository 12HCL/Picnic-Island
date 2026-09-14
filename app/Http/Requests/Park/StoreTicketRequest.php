<?php

namespace App\Http\Requests\Park;

use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An online sale. The visitor is the buyer; role:visitor middleware handles access.
 *
 * Note what is NOT validated here: available capacity. A form request runs outside the
 * transaction, so anything it checks can be stale by the time the row is written — BR-06
 * belongs in TicketSalesService under a row lock, and nowhere else. The rules below are the
 * shape of the input, not the business rule.
 */
class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:visitor middleware handles access
    }

    public function rules(): array
    {
        return [
            'park_event_id' => ['required', 'integer', 'exists:park_events,id'],
            // TINYINT UNSIGNED on the column; 10 is the practical per-transaction cap the
            // event page offers. A larger group books twice.
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
            'method' => ['required', Rule::in(PaymentService::METHODS)],
        ];
    }

    public function messages(): array
    {
        return [
            'park_event_id.exists' => 'That event no longer exists.',
            'quantity.max' => 'Up to 10 admissions per purchase. Book again for a larger group.',
        ];
    }
}
