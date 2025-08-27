<?php

namespace App\Http\Resources\TextXpert;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TextDifferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'data' => $this->resource,
            'metadata' => [
                'compared_at' => now()->toISOString(),
            ],
        ];
    }
}