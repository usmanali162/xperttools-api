<?php

namespace App\Http\Resources\UtiliXpert;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarcodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'data' => [
                'barcode' => $this->resource['barcode'],
                'input' => $this->resource['input'],
                'metadata' => $this->resource['metadata'],
                'download_info' => [
                    'filename' => 'barcode-' . strtolower($this->resource['barcode']['type']) . '.' . $this->resource['barcode']['format'],
                    'mime_type' => $this->getMimeType($this->resource['barcode']['format']),
                ],
            ],
        ];
    }

    private function getMimeType(string $format): string
    {
        return match ($format) {
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            'html' => 'text/html',
            default => 'application/octet-stream',
        };
    }
}