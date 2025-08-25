<?php

namespace App\Http\Requests\UtiliXpert;

use Illuminate\Foundation\Http\FormRequest;

class GenerateQRRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => 'required|string|max:2000',
            'format' => 'sometimes|string|in:png,svg',
            'size' => 'sometimes|integer|min:50|max:1000',
            'background_color' => 'sometimes|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'foreground_color' => 'sometimes|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => 'Please provide text or URL to generate QR code.',
            'text.max' => 'Text cannot exceed 2000 characters.',
            'format.in' => 'Format must be either png or svg.',
            'size.min' => 'Size must be at least 50 pixels.',
            'size.max' => 'Size cannot exceed 1000 pixels.',
            'background_color.regex' => 'Background color must be a valid hex color code.',
            'foreground_color.regex' => 'Foreground color must be a valid hex color code.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'format' => $this->input('format', 'png'),
            'size' => $this->input('size', 200),
        ]);
    }
}