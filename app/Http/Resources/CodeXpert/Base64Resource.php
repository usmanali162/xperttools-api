<?php

namespace App\Http\Resources\CodeXpert;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Base64Resource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'success' => $this->resource['success'] ?? false,
            'data' => $this->resource,
            'metadata' => [
                'processed_at' => now()->toISOString(),
                'tool' => 'base64_encoder_decoder',
            ],
        ];
    }
}