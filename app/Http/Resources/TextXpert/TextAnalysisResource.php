<?php

namespace App\Http\Resources\TextXpert;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TextAnalysisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'data' => [
                'analysis' => $this->resource,
                'summary' => [
                    'total_characters' => $this->resource['characters']['total'],
                    'total_words' => $this->resource['words']['total'],
                    'total_lines' => $this->resource['lines']['total'],
                    'total_paragraphs' => $this->resource['paragraphs']['total'],
                    'estimated_reading_time' => $this->resource['reading_stats']['reading_time_average'] . ' minutes',
                ],
                'metadata' => [
                    'analyzed_at' => now()->toISOString(),
                ],
            ],
        ];
    }
}