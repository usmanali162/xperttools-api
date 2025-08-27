<?php

namespace App\Http\Requests\TextXpert;

use Illuminate\Foundation\Http\FormRequest;

class GenerateLoremRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'sometimes|string|in:words,sentences,paragraphs',
            'count' => 'sometimes|integer|min:1|max:1000',
            'start_with_classic' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Type must be words, sentences, or paragraphs.',
            'count.min' => 'Count must be at least 1.',
            'count.max' => 'Count cannot exceed 1000.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' => $this->input('type', 'paragraphs'),
            'count' => $this->input('count', 3),
            'start_with_classic' => $this->input('start_with_classic', true),
        ]);
    }
}