<?php

namespace App\Http\Requests\UtiliXpert;

use Illuminate\Foundation\Http\FormRequest;

class GenerateUUIDRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'version' => 'sometimes|integer|in:1,3,4,5',
            'count' => 'sometimes|integer|min:1|max:100',
            'namespace' => 'sometimes|string|max:255',
            'name' => 'sometimes|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'version.in' => 'UUID version must be 1, 3, 4, or 5.',
            'count.min' => 'Count must be at least 1.',
            'count.max' => 'Cannot generate more than 100 UUIDs at once.',
            'namespace.max' => 'Namespace cannot exceed 255 characters.',
            'name.max' => 'Name cannot exceed 255 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'version' => $this->input('version', 4),
            'count' => $this->input('count', 1),
        ]);
    }
}