<?php

namespace App\Http\Requests\TextXpert;

use Illuminate\Foundation\Http\FormRequest;

class RemoveDuplicatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => 'required|string|max:1000000',
            'case_sensitive' => 'sometimes|boolean',
            'trim_whitespace' => 'sometimes|boolean',
            'ignore_empty_lines' => 'sometimes|boolean',
            'sort_order' => 'sometimes|string|in:preserve,alphabetical,reverse_alphabetical',
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => 'Please provide text to process.',
            'text.max' => 'Text cannot exceed 1MB in size.',
            'sort_order.in' => 'Sort order must be preserve, alphabetical, or reverse_alphabetical.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'case_sensitive' => $this->input('case_sensitive', true),
            'trim_whitespace' => $this->input('trim_whitespace', true),
            'ignore_empty_lines' => $this->input('ignore_empty_lines', true),
            'sort_order' => $this->input('sort_order', 'preserve'),
        ]);
    }
}