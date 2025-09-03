<?php

namespace App\Http\Requests\CodeXpert;

use Illuminate\Foundation\Http\FormRequest;

class Base64Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'input' => 'required|string|max:10000000', // 10MB max
            'operation' => 'required|string|in:encode,decode',
            'input_type' => 'sometimes|string|in:text,file,binary',
            'url_safe' => 'sometimes|boolean',
            'include_line_breaks' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'input.required' => 'Please provide input to encode or decode.',
            'input.max' => 'Input cannot exceed 10MB in size.',
            'operation.required' => 'Please specify operation (encode or decode).',
            'operation.in' => 'Operation must be either encode or decode.',
            'input_type.in' => 'Input type must be text, file, or binary.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'input_type' => $this->input('input_type', 'text'),
            'url_safe' => $this->input('url_safe', false),
            'include_line_breaks' => $this->input('include_line_breaks', false),
        ]);
    }
}