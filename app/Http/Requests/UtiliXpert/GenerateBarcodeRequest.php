<?php

namespace App\Http\Requests\UtiliXpert;

use Illuminate\Foundation\Http\FormRequest;

class GenerateBarcodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => 'required|string|max:100',
            'type' => 'sometimes|string|in:CODE_128,CODE_39,CODE_93,CODABAR,EAN_8,EAN_13,UPC_A,UPC_E,ITF_14,POSTNET',
            'format' => 'sometimes|string|in:png,svg,html',
            'width' => 'sometimes|integer|min:1|max:10',
            'height' => 'sometimes|integer|min:10|max:200',
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => 'Please provide text to encode in the barcode.',
            'text.max' => 'Text cannot exceed 100 characters.',
            'type.in' => 'Invalid barcode type selected.',
            'format.in' => 'Format must be png, svg, or html.',
            'width.min' => 'Width must be at least 1.',
            'width.max' => 'Width cannot exceed 10.',
            'height.min' => 'Height must be at least 10.',
            'height.max' => 'Height cannot exceed 200.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' => $this->input('type', 'CODE_128'),
            'format' => $this->input('format', 'png'),
            'width' => $this->input('width', 2),
            'height' => $this->input('height', 30),
        ]);
    }
}