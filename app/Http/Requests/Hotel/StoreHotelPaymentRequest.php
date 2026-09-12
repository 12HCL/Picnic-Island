<?php

namespace App\Http\Requests\Hotel;

use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHotelPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:visitor + booking ownership check done in controller
    }

    public function rules(): array
    {
        return [
            'method' => ['required', Rule::in(PaymentService::METHODS)],
        ];
    }

    public function messages(): array
    {
        return [
            'method.in' => 'Payment method must be card, cash, or transfer.',
        ];
    }
}
