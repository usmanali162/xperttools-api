<?php

namespace App\Http\Resources\TextXpert;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoremIpsumResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'data' => $this->resource,
            'metadata' => [
                'generated_at' => now()->toISOString(),
            ],
        ];
    }
}