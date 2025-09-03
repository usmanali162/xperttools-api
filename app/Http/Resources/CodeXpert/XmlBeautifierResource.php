<?php

namespace App\Http\Resources\CodeXpert;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class XmlBeautifierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'success' => $this->resource['valid'],
            'data' => $this->resource,
            'metadata' => [
                'processed_at' => now()->toISOString(),
                'tool' => 'xml_beautifier',
            ],
        ];
    }
}