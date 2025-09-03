<?php

namespace App\Http\Requests\CodeXpert;

use Illuminate\Foundation\Http\FormRequest;

class TestRegexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isReplacement = $this->input('operation') === 'replace';
        
        $rules = [
            'pattern' => 'required|string|max:5000',
            'test_string' => 'required|string|max:1000000',
            'flags' => 'sometimes|string|max:10',
            'global_match' => 'sometimes|boolean',
            'explain_pattern' => 'sometimes|boolean',
            'operation' => 'sometimes|string|in:test,replace',
        ];
        
        if ($isReplacement) {
            $rules['replacement'] = 'required|string|max:10000';
            $rules['global_replace'] = 'sometimes|boolean';
        }
        
        return $rules;
    }

    public function messages(): array
    {
        return [
            'pattern.required' => 'Please provide a regex pattern.',
            'pattern.max' => 'Pattern cannot exceed 5000 characters.',
            'test_string.required' => 'Please provide test string.',
            'test_string.max' => 'Test string cannot exceed 1MB.',
            'replacement.required' => 'Please provide replacement string for replace operation.',
            'replacement.max' => 'Replacement string cannot exceed 10000 characters.',
            'operation.in' => 'Operation must be either test or replace.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'flags' => $this->input('flags', ''),
            'global_match' => $this->input('global_match', true),
            'explain_pattern' => $this->input('explain_pattern', false),
            'operation' => $this->input('operation', 'test'),
            'global_replace' => $this->input('global_replace', true),
        ]);
    }
}