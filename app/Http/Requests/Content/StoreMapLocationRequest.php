<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMapLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:admin middleware handles access
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            // MASTER_SCHEMA.md §16 — exactly these five, nobody invents a sixth.
            'category' => ['required', Rule::in(['hotel', 'jetty', 'attraction', 'beach', 'facility'])],
            'description' => ['nullable', 'string', 'max:2000'],
            // BR-M5-02 (UC-18): percentages of the map image, not pixels. The column is
            // DECIMAL(5,2) and would accept up to 999.99, so the 0-100 bound lives here.
            'pos_x' => ['required', 'numeric', 'between:0,100'],
            'pos_y' => ['required', 'numeric', 'between:0,100'],
            'is_visible' => ['nullable', 'boolean'],
        ];
    }

    /**
     * An unchecked checkbox posts nothing at all, which would leave is_visible null and
     * violate the NOT NULL column. Default it here rather than in the controller.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['is_visible' => $this->boolean('is_visible')]);
    }

    public function messages(): array
    {
        return [
            'pos_x.between' => 'Horizontal position is a percentage across the map image, so it must be between 0 and 100.',
            'pos_y.between' => 'Vertical position is a percentage down the map image, so it must be between 0 and 100.',
        ];
    }
}
