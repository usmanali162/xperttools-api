<?php

namespace App\Http\Requests\UtiliXpert;

use Illuminate\Foundation\Http\FormRequest;

class ShortenUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => 'required|url|max:2000',
            'custom_code' => 'sometimes|string|max:50|regex:/^[a-zA-Z0-9\-_]+$/|unique:shortened_urls,short_code',
            'expiration_days' => 'sometimes|integer|min:1|max:365',
        ];
    }

    public function messages(): array
    {
        return [
            'url.required' => 'Please provide a URL to shorten.',
            'url.url' => 'Please provide a valid URL.',
            'url.max' => 'URL cannot exceed 2000 characters.',
            'custom_code.regex' => 'Custom code can only contain letters, numbers, hyphens, and underscores.',
            'custom_code.unique' => 'This custom code is already taken.',
            'expiration_days.max' => 'Expiration cannot exceed 365 days.',
        ];
    }
}