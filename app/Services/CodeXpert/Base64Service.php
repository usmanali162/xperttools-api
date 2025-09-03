<?php

namespace App\Services\CodeXpert;

use App\Services\BaseToolService;

class Base64Service extends BaseToolService
{
    public function process(
        string $input,
        string $operation = 'encode',
        string $inputType = 'text',
        bool $urlSafe = false,
        bool $includeLineBreaks = false
    ): array {
        $this->validateInput([
            'input' => $input,
            'operation' => $operation,
            'input_type' => $inputType,
            'url_safe' => $urlSafe,
            'include_line_breaks' => $includeLineBreaks,
        ], [
            'input' => 'required|string|max:10000000', // 10MB max
            'operation' => 'required|string|in:encode,decode',
            'input_type' => 'required|string|in:text,file,binary',
            'url_safe' => 'boolean',
            'include_line_breaks' => 'boolean',
        ]);

        try {
            $result = match ($operation) {
                'encode' => $this->encode($input, $inputType, $urlSafe, $includeLineBreaks),
                'decode' => $this->decode($input, $urlSafe),
            };

            $this->logUsage('base64_converter', [
                'operation' => $operation,
                'input_type' => $inputType,
                'input_length' => mb_strlen($input),
                'url_safe' => $urlSafe,
            ]);

            return array_merge($result, [
                'operation' => $operation,
                'options' => [
                    'input_type' => $inputType,
                    'url_safe' => $urlSafe,
                    'include_line_breaks' => $includeLineBreaks,
                ],
            ]);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'output' => null,
                'operation' => $operation,
            ];
        }
    }

    private function encode(
        string $input, 
        string $inputType, 
        bool $urlSafe, 
        bool $includeLineBreaks
    ): array {
        // Handle different input types
        $dataToEncode = match ($inputType) {
            'text' => $input,
            'binary' => $this->processBinaryInput($input),
            'file' => $this->processFileInput($input),
        };

        // Perform encoding
        if ($urlSafe) {
            $encoded = $this->base64UrlEncode($dataToEncode);
        } else {
            $encoded = base64_encode($dataToEncode);
        }

        // Add line breaks if requested (standard MIME format - 76 chars per line)
        if ($includeLineBreaks && !$urlSafe) {
            $encoded = chunk_split($encoded, 76, "\n");
            $encoded = rtrim($encoded); // Remove trailing newline
        }

        return [
            'success' => true,
            'output' => $encoded,
            'statistics' => $this->calculateEncodingStats($dataToEncode, $encoded),
            'encoding_info' => [
                'original_size' => mb_strlen($dataToEncode, '8bit'),
                'encoded_size' => mb_strlen($encoded),
                'size_increase' => $this->calculateSizeIncrease($dataToEncode, $encoded),
                'is_url_safe' => $urlSafe,
                'has_line_breaks' => $includeLineBreaks,
            ],
        ];
    }

    private function decode(string $input, bool $urlSafe): array
    {
        // Remove whitespace and line breaks for standard base64
        if (!$urlSafe) {
            $input = preg_replace('/\s+/', '', $input);
        }

        // Validate base64 format
        if (!$this->isValidBase64($input, $urlSafe)) {
            throw new \InvalidArgumentException('Invalid Base64 string format');
        }

        // Perform decoding
        if ($urlSafe) {
            $decoded = $this->base64UrlDecode($input);
        } else {
            $decoded = base64_decode($input, true);
        }

        if ($decoded === false) {
            throw new \InvalidArgumentException('Failed to decode Base64 string');
        }

        // Detect output type
        $outputType = $this->detectContentType($decoded);
        $isText = $this->isBinaryText($decoded);

        return [
            'success' => true,
            'output' => $decoded,
            'output_type' => $outputType,
            'is_text' => $isText,
            'statistics' => $this->calculateDecodingStats($input, $decoded),
            'decoding_info' => [
                'encoded_size' => mb_strlen($input),
                'decoded_size' => mb_strlen($decoded, '8bit'),
                'detected_type' => $outputType,
                'is_printable' => $isText,
            ],
        ];
    }

    private function base64UrlEncode(string $data): string
    {
        $encoded = base64_encode($data);
        $encoded = strtr($encoded, '+/', '-_');
        return rtrim($encoded, '=');
    }

    private function base64UrlDecode(string $data): string|false
    {
        $data = strtr($data, '-_', '+/');
        $padding = strlen($data) % 4;
        if ($padding) {
            $data .= str_repeat('=', 4 - $padding);
        }
        return base64_decode($data, true);
    }

    private function isValidBase64(string $input, bool $urlSafe): bool
    {
        if ($urlSafe) {
            // URL-safe base64 pattern
            return preg_match('/^[A-Za-z0-9\-_]*$/', $input) === 1;
        } else {
            // Standard base64 pattern (already stripped of whitespace)
            return preg_match('/^[A-Za-z0-9+\/]*={0,2}$/', $input) === 1;
        }
    }

    private function processBinaryInput(string $input): string
    {
        // Assume input is hex string representation of binary data
        if (preg_match('/^[0-9a-fA-F]+$/', $input)) {
            return hex2bin($input) ?: $input;
        }
        
        return $input;
    }

    private function processFileInput(string $input): string
    {
        // In a real implementation, this would handle file uploads
        // For now, treat it as regular text
        return $input;
    }

    private function detectContentType(string $data): string
    {
        // Check for common file signatures (magic numbers)
        $firstBytes = substr($data, 0, 16);
        $hex = bin2hex($firstBytes);

        $signatures = [
            '89504e47' => 'image/png',
            'ffd8ff' => 'image/jpeg',
            '47494638' => 'image/gif',
            '25504446' => 'application/pdf',
            '504b0304' => 'application/zip',
            '1f8b' => 'application/gzip',
            '7b22' => 'application/json', // {"
            '3c3f786d6c' => 'application/xml', // <?xml
            '3c68746d6c' => 'text/html', // <html
        ];

        foreach ($signatures as $signature => $type) {
            if (str_starts_with($hex, $signature)) {
                return $type;
            }
        }

        // Check if it's printable text
        if ($this->isBinaryText($data)) {
            return 'text/plain';
        }

        return 'application/octet-stream';
    }

    private function isBinaryText(string $data): bool
    {
        // Check if the string contains only printable characters
        // Allow common whitespace characters
        return preg_match('/^[\x20-\x7E\t\r\n]+$/', $data) === 1;
    }

    private function calculateSizeIncrease(string $original, string $encoded): string
    {
        $originalSize = mb_strlen($original, '8bit');
        $encodedSize = mb_strlen($encoded);
        
        if ($originalSize === 0) {
            return '0%';
        }

        $increase = (($encodedSize - $originalSize) / $originalSize) * 100;
        return round($increase, 2) . '%';
    }

    private function calculateEncodingStats(string $original, string $encoded): array
    {
        return [
            'original_length' => mb_strlen($original, '8bit'),
            'encoded_length' => mb_strlen($encoded),
            'compression_ratio' => round(mb_strlen($encoded) / max(1, mb_strlen($original, '8bit')), 2),
            'character_distribution' => $this->analyzeCharacterDistribution($encoded),
        ];
    }

    private function calculateDecodingStats(string $encoded, string $decoded): array
    {
        return [
            'encoded_length' => mb_strlen($encoded),
            'decoded_length' => mb_strlen($decoded, '8bit'),
            'compression_ratio' => round(mb_strlen($decoded, '8bit') / max(1, mb_strlen($encoded)), 2),
            'contains_binary' => !$this->isBinaryText($decoded),
        ];
    }

    private function analyzeCharacterDistribution(string $base64): array
    {
        $chars = count_chars($base64, 1);
        $total = mb_strlen($base64);
        
        $distribution = [
            'uppercase' => 0,
            'lowercase' => 0,
            'digits' => 0,
            'plus' => 0,
            'slash' => 0,
            'equals' => 0,
            'hyphen' => 0,
            'underscore' => 0,
        ];

        foreach ($chars as $ord => $count) {
            $char = chr($ord);
            if ($char >= 'A' && $char <= 'Z') {
                $distribution['uppercase'] += $count;
            } elseif ($char >= 'a' && $char <= 'z') {
                $distribution['lowercase'] += $count;
            } elseif ($char >= '0' && $char <= '9') {
                $distribution['digits'] += $count;
            } elseif ($char === '+') {
                $distribution['plus'] = $count;
            } elseif ($char === '/') {
                $distribution['slash'] = $count;
            } elseif ($char === '=') {
                $distribution['equals'] = $count;
            } elseif ($char === '-') {
                $distribution['hyphen'] = $count;
            } elseif ($char === '_') {
                $distribution['underscore'] = $count;
            }
        }

        // Convert to percentages
        foreach ($distribution as &$value) {
            $value = $total > 0 ? round(($value / $total) * 100, 2) : 0;
        }

        return $distribution;
    }

    public function getOptions(): array
    {
        return [
            'operations' => [
                'encode' => 'Convert text or binary data to Base64',
                'decode' => 'Convert Base64 back to original format',
            ],
            'input_types' => [
                'text' => 'Plain text input',
                'binary' => 'Binary data (hex string)',
                'file' => 'File upload (future feature)',
            ],
            'encoding_options' => [
                'url_safe' => [
                    'description' => 'Use URL-safe Base64 encoding (replaces +/ with -_)',
                    'default' => false,
                ],
                'include_line_breaks' => [
                    'description' => 'Add line breaks every 76 characters (MIME standard)',
                    'default' => false,
                ],
            ],
            'features' => [
                'standard_base64' => 'Standard Base64 encoding (RFC 4648)',
                'url_safe_base64' => 'URL and filename safe encoding',
                'binary_detection' => 'Automatic content type detection',
                'statistics' => 'Encoding/decoding statistics',
                'validation' => 'Input validation and error handling',
            ],
            'examples' => [
                'text_encode' => [
                    'input' => 'Hello, World!',
                    'output' => 'SGVsbG8sIFdvcmxkIQ==',
                ],
                'url_safe' => [
                    'input' => 'Hello, World!',
                    'output' => 'SGVsbG8sIFdvcmxkIQ',
                ],
                'with_line_breaks' => [
                    'input' => 'The quick brown fox jumps over the lazy dog. The quick brown fox jumps over the lazy dog.',
                    'output' => 'VGhlIHF1aWNrIGJyb3duIGZveCBqdW1wcyBvdmVyIHRoZSBsYXp5IGRvZy4gVGhlIHF1aWNr\nIGJyb3duIGZveCBqdW1wcyBvdmVyIHRoZSBsYXp5IGRvZy4=',
                ],
            ],
            'size_limits' => [
                'max_input_size' => '10MB',
                'recommended_size' => '1MB for optimal performance',
            ],
        ];
    }
}