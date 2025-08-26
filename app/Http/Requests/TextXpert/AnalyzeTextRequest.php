<?php

namespace App\Http\Requests\TextXpert;

use Illuminate\Foundation\Http\FormRequest;

class AnalyzeTextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => 'required|string|max:1000000', // 1MB max
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => 'Please provide text to analyze.',
            'text.max' => 'Text cannot exceed 1MB in size.',
        ];
    }
}