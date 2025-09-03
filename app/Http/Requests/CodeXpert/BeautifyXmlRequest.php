<?php

namespace App\Http\Requests\CodeXpert;

use Illuminate\Foundation\Http\FormRequest;

class BeautifyXmlRequest extends FormRequest
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
            'validate_only' => 'sometimes|boolean',
            'remove_comments' => 'sometimes|boolean',
            'sort_attributes' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'input.required' => 'Please provide XML to beautify or validate.',
            'input.max' => 'XML input cannot exceed 5MB in size.',
            'indent_size.min' => 'Indent size cannot be negative.',
            'indent_size.max' => 'Indent size cannot exceed 8 spaces.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'indent_size' => $this->input('indent_size', 2),
            'validate_only' => $this->input('validate_only', false),
            'remove_comments' => $this->input('remove_comments', false),
            'sort_attributes' => $this->input('sort_attributes', false),
        ]);
    }
}