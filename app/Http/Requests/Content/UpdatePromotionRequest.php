<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:admin middleware handles access
    }

    /**
     * Same shape as StorePromotionRequest. Kept as its own class rather than extending it,
     * matching how the map location pair is written in this module.
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'body' => ['nullable', 'string', 'max:5000'],
            'module' => ['required', Rule::in(['hotel', 'ferry', 'park', 'general'])],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'is_published' => ['nullable', 'boolean'],
            // Optional on update: leaving it empty keeps the existing image rather than
            // clearing it. The controller only touches image_path when a file arrives.
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }

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
