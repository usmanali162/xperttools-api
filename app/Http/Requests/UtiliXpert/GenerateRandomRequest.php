<?php

namespace App\Http\Requests\UtiliXpert;

use Illuminate\Foundation\Http\FormRequest;

class GenerateRandomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = $this->input('generator_type', 'number');
        
        $rules = [
            'generator_type' => 'required|string|in:number,name,string',
        ];

        if ($type === 'number') {
            $rules = array_merge($rules, [
                'min' => 'required|integer',
                'max' => 'required|integer|gt:min',
                'count' => 'sometimes|integer|min:1|max:1000',
                'unique' => 'sometimes|boolean',
            ]);
        } elseif ($type === 'name') {
            $rules = array_merge($rules, [
                'count' => 'sometimes|integer|min:1|max:100',
                'gender' => 'sometimes|string|in:male,female,unisex',
                'include_last_name' => 'sometimes|boolean',
            ]);
        } elseif ($type === 'string') {
            $rules = array_merge($rules, [
                'length' => 'required|integer|min:1|max:1000',
                'string_type' => 'sometimes|string|in:alphabetic,numeric,alphanumeric,mixed,symbols',
            ]);
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'generator_type.required' => 'Please specify the generator type.',
            'generator_type.in' => 'Generator type must be number, name, or string.',
            'min.required' => 'Minimum value is required for number generation.',
            'max.required' => 'Maximum value is required for number generation.',
            'max.gt' => 'Maximum value must be greater than minimum value.',
            'count.max' => 'Cannot generate more than 1000 numbers at once.',
            'length.max' => 'String length cannot exceed 1000 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $defaults = [
            'count' => 1,
            'unique' => false,
            'gender' => 'unisex',
            'include_last_name' => true,
            'string_type' => 'mixed',
        ];

        foreach ($defaults as $key => $value) {
            if (!$this->has($key)) {
                $this->merge([$key => $value]);
            }
        }
    }
}