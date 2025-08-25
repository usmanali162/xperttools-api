<?php

namespace App\Http\Resources\UtiliXpert;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QRCodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'data' => [
                'qr_code' => [
                    'data_url' => $this->resource['data_url'],
                    'format' => $this->resource['format'],
                    'size' => $this->resource['size'],
                    'file_size' => $this->resource['file_size'],
                    'download_name' => $this->resource['download_name'],
                ],
                'input' => [
                    'text' => $this->resource['text'],
                ],
                'metadata' => [
                    'text_length' => strlen($this->resource['text']),
                    'generated_at' => now()->toISOString(),
                ],
            ],
        ];
    }
}