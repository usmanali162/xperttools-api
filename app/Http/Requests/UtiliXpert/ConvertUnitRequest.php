<?php

namespace App\Http\Requests\UtiliXpert;

use Illuminate\Foundation\Http\FormRequest;

class ConvertUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // No authentication required for free tools
    }

    public function rules(): array
    {
        return [
            'value' => 'required|numeric',
            'from_unit' => 'required|string|max:50',
            'to_unit' => 'required|string|max:50',
            'category' => 'required|string|in:length,weight,temperature,area,volume',
        ];
    }

    public function messages(): array
    {
        return [
            'value.required' => 'Please provide a value to convert.',
            'value.numeric' => 'The value must be a number.',
            'from_unit.required' => 'Please specify the unit to convert from.',
            'to_unit.required' => 'Please specify the unit to convert to.',
            'category.required' => 'Please specify the category of conversion.',
            'category.in' => 'Invalid category. Supported categories: length, weight, temperature, area, volume.',
        ];
    }
}