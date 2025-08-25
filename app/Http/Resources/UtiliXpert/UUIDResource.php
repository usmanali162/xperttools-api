<?php

namespace App\Http\Resources\UtiliXpert;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UUIDResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'data' => [
                'uuids' => $this->resource['uuids'],
                'metadata' => $this->resource['metadata'],
                'info' => [
                    'total_generated' => count($this->resource['uuids']),
                    'version_info' => $this->getVersionInfo($this->resource['metadata']['version']),
                ],
            ],
        ];
    }

    private function getVersionInfo(int $version): string
    {
        return match ($version) {
            1 => 'Time-based UUID using MAC address and timestamp',
            3 => 'Name-based UUID using MD5 hash',
            4 => 'Random UUID using cryptographically secure random numbers',
            5 => 'Name-based UUID using SHA-1 hash',
            default => 'Random UUID',
        };
    }
}