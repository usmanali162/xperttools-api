<?php

namespace App\Http\Requests\TextXpert;

use Illuminate\Foundation\Http\FormRequest;

class ConvertCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supportedCases = 'lower,upper,title,sentence,camel,pascal,snake,kebab,constant,dot,path,alternating,inverse';
        
        return [
            'text' => 'required|string|max:500000',
            'case_type' => 'sometimes|string|in:' . $supportedCases,
            'convert_all' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => 'Please provide text to convert.',
            'text.max' => 'Text cannot exceed 500KB in size.',
            'case_type.in' => 'Invalid case type selected.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'convert_all' => $this->input('convert_all', false),
        ]);
    }
}