<?php

namespace App\Http\Requests\TextXpert;

use Illuminate\Foundation\Http\FormRequest;

class CompareTextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'original_text' => 'required|string|max:500000',
            'modified_text' => 'required|string|max:500000',
            'comparison_type' => 'sometimes|string|in:character,word,line',
        ];
    }

    public function messages(): array
    {
        return [
            'original_text.required' => 'Please provide the original text.',
            'modified_text.required' => 'Please provide the modified text.',
            'original_text.max' => 'Original text cannot exceed 500KB in size.',
            'modified_text.max' => 'Modified text cannot exceed 500KB in size.',
            'comparison_type.in' => 'Comparison type must be character, word, or line.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'comparison_type' => $this->input('comparison_type', 'word'),
        ]);
    }
}