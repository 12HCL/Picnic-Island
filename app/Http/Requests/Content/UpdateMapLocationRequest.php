<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMapLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:admin middleware handles access
    }

    /**
     * Identical to the store rules. Kept as a separate class rather than shared, matching
     * the Module 2 and Module 4 request pairs — the two diverge the moment either side
     * gains a rule the other should not have.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(['hotel', 'jetty', 'attraction', 'beach', 'facility'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'pos_x' => ['required', 'numeric', 'between:0,100'],
            'pos_y' => ['required', 'numeric', 'between:0,100'],
            'is_visible' => ['nullable', 'boolean'],
        ];
    }

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
