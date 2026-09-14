<?php

namespace App\Http\Requests\Park;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateParkActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:park_staff middleware handles access
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            // MASTER_SCHEMA.md §12 — beach events are a type here, not a separate table.
            'type' => ['required', Rule::in(['ride', 'show', 'beach_event'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'map_location_id' => ['nullable', 'integer', 'exists:map_locations,id'],
            // SMALLINT UNSIGNED. Nothing on this island seats more than 65535.
            'default_capacity' => ['required', 'integer', 'min:1', 'max:65535'],
            'base_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * An unchecked checkbox posts nothing at all, which would leave is_active null and
     * violate the NOT NULL column. Default it here rather than in the controller.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function messages(): array
    {
        return [
            'default_capacity.min' => 'An activity with no capacity cannot be scheduled.',
        ];
    }
}
