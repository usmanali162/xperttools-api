<?php

namespace App\Services\UtiliXpert;

use App\Services\BaseToolService;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class UUIDGeneratorService extends BaseToolService
{
    private array $supportedVersions = [1, 3, 4, 5];

    public function generate(
        int $version = 4,
        int $count = 1,
        ?string $namespace = null,
        ?string $name = null
    ): array {
        $this->validateInput([
            'version' => $version,
            'count' => $count,
        ], [
            'version' => 'required|integer|in:1,3,4,5',
            'count' => 'required|integer|min:1|max:100',
        ]);

        $uuids = [];
        
        for ($i = 0; $i < $count; $i++) {
            $uuid = match ($version) {
                1 => Uuid::uuid1()->toString(),
                3 => $this->generateUuid3($namespace, $name . $i),
                4 => Uuid::uuid4()->toString(),
                5 => $this->generateUuid5($namespace, $name . $i),
                default => Uuid::uuid4()->toString(),
            };
            
            $uuids[] = [
                'uuid' => $uuid,
                'version' => $version,
                'format' => 'standard',
                'uppercase' => strtoupper($uuid),
                'no_hyphens' => str_replace('-', '', $uuid),
                'braces' => '{' . $uuid . '}',
            ];
        }

        $this->logUsage('uuid_generator', [
            'version' => $version,
            'count' => $count,
        ]);

        return [
            'uuids' => $uuids,
            'metadata' => [
                'version' => $version,
                'count' => $count,
                'generated_at' => now()->toISOString(),
            ],
        ];
    }

    private function generateUuid3(?string $namespace, ?string $name): string
    {
        $namespace = $namespace ?: Uuid::NAMESPACE_URL;
        $name = $name ?: 'default-name';
        return Uuid::uuid3($namespace, $name)->toString();
    }

    private function generateUuid5(?string $namespace, ?string $name): string
    {
        $namespace = $namespace ?: Uuid::NAMESPACE_URL;
        $name = $name ?: 'default-name';
        return Uuid::uuid5($namespace, $name)->toString();
    }

    public function getSupportedOptions(): array
    {
        return [
            'versions' => [
                1 => 'Time-based UUID',
                3 => 'Name-based (MD5)',
                4 => 'Random UUID',
                5 => 'Name-based (SHA-1)',
            ],
            'max_count' => 100,
            'formats' => [
                'standard' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
                'uppercase' => 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX',
                'no_hyphens' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
                'braces' => '{xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx}',
            ],
        ];
    }
}