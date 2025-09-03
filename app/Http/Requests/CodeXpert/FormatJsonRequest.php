<?php

namespace App\Http\Requests\CodeXpert;

use Illuminate\Foundation\Http\FormRequest;

class FormatJsonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'input' => 'required|string|max:5000000',
            'indent_size' => 'sometimes|integer|min:0|max:8',
            'sort_keys' => 'sometimes|boolean',
            'validate_only' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'input.required' => 'Please provide JSON to format or validate.',
            'input.max' => 'JSON input cannot exceed 5MB in size.',
            'indent_size.min' => 'Indent size cannot be negative.',
            'indent_size.max' => 'Indent size cannot exceed 8 spaces.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'indent_size' => $this->input('indent_size', 2),
            'sort_keys' => $this->input('sort_keys', false),
            'validate_only' => $this->input('validate_only', false),
        ]);
    }
}