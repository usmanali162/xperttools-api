<?php

namespace App\Http\Resources\TextXpert;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaseConversionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'data' => $this->resource,
            'metadata' => [
                'converted_at' => now()->toISOString(),
            ],
        ];
    }
}