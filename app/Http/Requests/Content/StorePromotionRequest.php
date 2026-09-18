<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:admin middleware handles access
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'body' => ['nullable', 'string', 'max:5000'],
            // MASTER_SCHEMA.md §15 — exactly these four, nobody invents a fifth.
            'module' => ['required', Rule::in(['hotel', 'ferry', 'park', 'general'])],
            'starts_on' => ['required', 'date'],
            // A promotion that ends before it starts would never display, and nothing
            // downstream would report it as a fault. Cheaper to refuse here.
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'is_published' => ['nullable', 'boolean'],
            // The column stores a path, not the file. The controller puts the upload on the
            // public disk and saves what store() returns.
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }

    /**
     * An unchecked checkbox posts nothing at all, which would leave is_published null and
     * violate the NOT NULL column. Default it here rather than in the controller.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['is_published' => $this->boolean('is_published')]);
    }

    public function messages(): array
    {
        return [
            'ends_on.after_or_equal' => 'A promotion cannot end before it starts.',
            'image.max' => 'The image must be 2 MB or smaller.',
        ];
    }
}
