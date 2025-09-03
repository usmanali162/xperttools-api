<?php

namespace App\Services\CodeXpert;

use App\Services\BaseToolService;

class JsonFormatterService extends BaseToolService
{
    public function formatJson(
        string $input,
        int $indentSize = 2,
        bool $sortKeys = false,
        bool $validateOnly = false
    ): array {
        $this->validateInput([
            'input' => $input,
            'indent_size' => $indentSize,
            'sort_keys' => $sortKeys,
            'validate_only' => $validateOnly,
        ], [
            'input' => 'required|string|max:5000000',
            'indent_size' => 'integer|min:0|max:8',
            'sort_keys' => 'boolean',
            'validate_only' => 'boolean',
        ]);

        $validationResult = $this->validateJson($input);
        
        if (!$validationResult['valid']) {
            return [
                'valid' => false,
                'errors' => $validationResult['errors'],
                'formatted_json' => null,
                'minified_json' => null,
                'statistics' => $this->calculateStatistics($input, null),
            ];
        }

        $decoded = json_decode($input, true);

        if ($validateOnly) {
            return [
                'valid' => true,
                'errors' => [],
                'formatted_json' => null,
                'minified_json' => null,
                'statistics' => $this->calculateStatistics($input, $decoded),
            ];
        }

        if ($sortKeys) {
            $decoded = $this->sortArrayRecursive($decoded);
        }

        $formattedJson = json_encode(
            $decoded,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            512
        );

        if ($indentSize !== 4) {
            $formattedJson = $this->adjustIndentation($formattedJson, $indentSize);
        }

        $minifiedJson = json_encode(
            $decoded,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        $this->logUsage('json_formatter', [
            'input_length' => mb_strlen($input),
            'indent_size' => $indentSize,
            'sort_keys' => $sortKeys,
            'validate_only' => $validateOnly,
        ]);

        return [
            'valid' => true,
            'errors' => [],
            'formatted_json' => $formattedJson,
            'minified_json' => $minifiedJson,
            'statistics' => $this->calculateStatistics($input, $decoded),
            'options' => [
                'indent_size' => $indentSize,
                'sort_keys' => $sortKeys,
            ],
        ];
    }

    private function validateJson(string $json): array
    {
        json_decode($json);
        $error = json_last_error();
        
        if ($error === JSON_ERROR_NONE) {
            return [
                'valid' => true,
                'errors' => [],
            ];
        }

        $errorMessage = match ($error) {
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters',
            JSON_ERROR_RECURSION => 'Recursive references detected',
            JSON_ERROR_INF_OR_NAN => 'Inf or NaN value cannot be JSON encoded',
            JSON_ERROR_UNSUPPORTED_TYPE => 'Unsupported data type',
            JSON_ERROR_INVALID_PROPERTY_NAME => 'Invalid property name',
            JSON_ERROR_UTF16 => 'Malformed UTF-16 characters',
            default => 'Unknown JSON error',
        };

        // Try to find the approximate error location
        $errorLocation = $this->findErrorLocation($json);

        return [
            'valid' => false,
            'errors' => [
                [
                    'message' => $errorMessage,
                    'code' => $error,
                    'location' => $errorLocation,
                ],
            ],
        ];
    }

    private function findErrorLocation(string $json): ?array
    {
        // Simple error location detection based on parsing attempts
        $lines = explode("\n", $json);
        $lineCount = count($lines);
        
        // Try to parse progressively to find where it breaks
        for ($i = 0; $i < $lineCount; $i++) {
            $partial = implode("\n", array_slice($lines, 0, $i + 1));
            json_decode($partial);
            
            if (json_last_error() !== JSON_ERROR_NONE && $i > 0) {
                return [
                    'line' => $i + 1,
                    'column' => mb_strlen($lines[$i]) + 1,
                    'snippet' => $lines[$i],
                ];
            }
        }

        return null;
    }

    private function sortArrayRecursive($array)
    {
        if (!is_array($array)) {
            return $array;
        }

        // Check if it's an associative array
        if ($this->isAssociativeArray($array)) {
            ksort($array, SORT_STRING);
        }

        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = $this->sortArrayRecursive($value);
            }
        }

        return $array;
    }

    private function isAssociativeArray($array): bool
    {
        if (!is_array($array) || empty($array)) {
            return false;
        }
        
        return array_keys($array) !== range(0, count($array) - 1);
    }

    private function adjustIndentation(string $json, int $indentSize): string
    {
        if ($indentSize === 0) {
            return preg_replace('/^\s+/m', '', $json);
        }

        $indent = str_repeat(' ', $indentSize);
        
        return preg_replace_callback('/^(\s+)/m', function ($matches) use ($indentSize) {
            $level = strlen($matches[1]) / 4; // Default JSON_PRETTY_PRINT uses 4 spaces
            return str_repeat(str_repeat(' ', $indentSize), (int)$level);
        }, $json);
    }

    private function calculateStatistics(string $input, $decoded): array
    {
        $stats = [
            'input_size' => mb_strlen($input),
            'input_size_formatted' => $this->formatBytes(mb_strlen($input)),
        ];

        if ($decoded !== null) {
            $stats['depth'] = $this->calculateDepth($decoded);
            $stats['element_count'] = $this->countElements($decoded);
            $stats['type'] = $this->determineType($decoded);
            
            if (is_array($decoded)) {
                $stats['keys'] = $this->isAssociativeArray($decoded) 
                    ? array_keys($decoded) 
                    : null;
            }
        }

        return $stats;
    }

    private function calculateDepth($data, int $currentDepth = 0): int
    {
        if (!is_array($data)) {
            return $currentDepth;
        }

        $maxDepth = $currentDepth;
        
        foreach ($data as $value) {
            if (is_array($value)) {
                $depth = $this->calculateDepth($value, $currentDepth + 1);
                $maxDepth = max($maxDepth, $depth);
            }
        }

        return $maxDepth;
    }

    private function countElements($data): int
    {
        if (!is_array($data)) {
            return 1;
        }

        $count = count($data);
        
        foreach ($data as $value) {
            if (is_array($value)) {
                $count += $this->countElements($value);
            }
        }

        return $count;
    }

    private function determineType($data): string
    {
        if (is_array($data)) {
            return $this->isAssociativeArray($data) ? 'object' : 'array';
        }
        
        return gettype($data);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        
        $units = ['KB', 'MB', 'GB'];
        $unitIndex = 0;
        $size = $bytes / 1024;
        
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }
        
        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    public function getOptions(): array
    {
        return [
            'formatting_options' => [
                'indent_sizes' => [0, 2, 4, 8],
                'sort_keys' => [
                    'description' => 'Sort object keys alphabetically',
                    'default' => false,
                ],
                'validate_only' => [
                    'description' => 'Only validate JSON without formatting',
                    'default' => false,
                ],
            ],
            'supported_features' => [
                'format' => 'Pretty print JSON with customizable indentation',
                'minify' => 'Compress JSON by removing whitespace',
                'validate' => 'Check if JSON is valid with detailed error messages',
                'sort' => 'Sort object keys alphabetically',
                'statistics' => 'Get JSON structure statistics',
            ],
            'examples' => [
                'simple' => '{"name":"John","age":30}',
                'nested' => '{"user":{"name":"John","contacts":{"email":"john@example.com"}}}',
                'array' => '["apple","banana","cherry"]',
                'mixed' => '{"items":[1,2,3],"total":6,"active":true}',
            ],
        ];
    }
}