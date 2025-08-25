<?php

namespace App\Http\Resources\UtiliXpert;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'data' => [
                'original' => [
                    'value' => $this->resource['original_value'],
                    'unit' => $this->resource['original_unit'],
                ],
                'converted' => [
                    'value' => $this->resource['converted_value'],
                    'unit' => $this->resource['converted_unit'],
                ],
                'category' => $this->resource['category'],
                'formula' => $this->resource['formula'],
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}