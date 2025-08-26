<?php

namespace App\Services\TextXpert;

use App\Services\BaseToolService;

class CaseConverterService extends BaseToolService
{
    private array $supportedCases = [
        'lower' => 'lowercase',
        'upper' => 'UPPERCASE',
        'title' => 'Title Case',
        'sentence' => 'Sentence case',
        'camel' => 'camelCase',
        'pascal' => 'PascalCase',
        'snake' => 'snake_case',
        'kebab' => 'kebab-case',
        'constant' => 'CONSTANT_CASE',
        'dot' => 'dot.case',
        'path' => 'path/case',
        'alternating' => 'aLtErNaTiNg CaSe',
        'inverse' => 'iNVERSE cASE',
    ];

    public function convertCase(string $text, string $caseType): array
    {
        $this->validateInput([
            'text' => $text,
            'case_type' => $caseType,
        ], [
            'text' => 'required|string|max:500000',
            'case_type' => 'required|string|in:' . implode(',', array_keys($this->supportedCases)),
        ]);

        $convertedText = match ($caseType) {
            'lower' => mb_strtolower($text),
            'upper' => mb_strtoupper($text),
            'title' => $this->convertToTitleCase($text),
            'sentence' => $this->convertToSentenceCase($text),
            'camel' => $this->convertToCamelCase($text),
            'pascal' => $this->convertToPascalCase($text),
            'snake' => $this->convertToSnakeCase($text),
            'kebab' => $this->convertToKebabCase($text),
            'constant' => $this->convertToConstantCase($text),
            'dot' => $this->convertToDotCase($text),
            'path' => $this->convertToPathCase($text),
            'alternating' => $this->convertToAlternatingCase($text),
            'inverse' => $this->convertToInverseCase($text),
            default => $text,
        };

        $this->logUsage('case_converter', [
            'case_type' => $caseType,
            'text_length' => mb_strlen($text),
        ]);

        return [
            'original_text' => $text,
            'converted_text' => $convertedText,
            'case_type' => $caseType,
            'case_name' => $this->supportedCases[$caseType],
            'statistics' => [
                'original_length' => mb_strlen($text),
                'converted_length' => mb_strlen($convertedText),
                'character_difference' => mb_strlen($convertedText) - mb_strlen($text),
            ],
        ];
    }

    public function convertMultipleCases(string $text): array
    {
        $this->validateInput(['text' => $text], [
            'text' => 'required|string|max:10000', // Smaller limit for multiple conversions
        ]);

        $results = [];
        
        foreach ($this->supportedCases as $caseType => $caseName) {
            $conversion = $this->convertCase($text, $caseType);
            $results[$caseType] = [
                'name' => $caseName,
                'result' => $conversion['converted_text'],
            ];
        }

        $this->logUsage('case_converter_multiple', [
            'text_length' => mb_strlen($text),
            'cases_converted' => count($results),
        ]);

        return [
            'original_text' => $text,
            'conversions' => $results,
        ];
    }

    private function convertToTitleCase(string $text): string
    {
        $words = preg_split('/(\s+)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $titleCased = [];

        foreach ($words as $word) {
            if (preg_match('/^\s+$/', $word)) {
                $titleCased[] = $word; // Preserve whitespace
            } else {
                $titleCased[] = mb_strtoupper(mb_substr($word, 0, 1)) . mb_strtolower(mb_substr($word, 1));
            }
        }

        return implode('', $titleCased);
    }

    private function convertToSentenceCase(string $text): string
    {
        $text = mb_strtolower($text);
        return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
    }

    private function convertToCamelCase(string $text): string
    {
        $text = preg_replace('/[^a-zA-Z0-9]/', ' ', $text);
        $words = array_filter(explode(' ', $text));
        
        if (empty($words)) return '';
        
        $camelCase = mb_strtolower($words[0]);
        
        for ($i = 1; $i < count($words); $i++) {
            $camelCase .= mb_strtoupper(mb_substr($words[$i], 0, 1)) . mb_strtolower(mb_substr($words[$i], 1));
        }
        
        return $camelCase;
    }

    private function convertToPascalCase(string $text): string
    {
        $camelCase = $this->convertToCamelCase($text);
        return mb_strtoupper(mb_substr($camelCase, 0, 1)) . mb_substr($camelCase, 1);
    }

    private function convertToSnakeCase(string $text): string
    {
        $text = preg_replace('/[^a-zA-Z0-9]/', ' ', $text);
        $words = array_filter(explode(' ', $text));
        return mb_strtolower(implode('_', $words));
    }

    private function convertToKebabCase(string $text): string
    {
        $text = preg_replace('/[^a-zA-Z0-9]/', ' ', $text);
        $words = array_filter(explode(' ', $text));
        return mb_strtolower(implode('-', $words));
    }

    private function convertToConstantCase(string $text): string
    {
        $text = preg_replace('/[^a-zA-Z0-9]/', ' ', $text);
        $words = array_filter(explode(' ', $text));
        return mb_strtoupper(implode('_', $words));
    }

    private function convertToDotCase(string $text): string
    {
        $text = preg_replace('/[^a-zA-Z0-9]/', ' ', $text);
        $words = array_filter(explode(' ', $text));
        return mb_strtolower(implode('.', $words));
    }

    private function convertToPathCase(string $text): string
    {
        $text = preg_replace('/[^a-zA-Z0-9]/', ' ', $text);
        $words = array_filter(explode(' ', $text));
        return mb_strtolower(implode('/', $words));
    }

    private function convertToAlternatingCase(string $text): string
    {
        $result = '';
        $isUpper = false;
        
        for ($i = 0; $i < mb_strlen($text); $i++) {
            $char = mb_substr($text, $i, 1);
            
            if (preg_match('/[a-zA-Z]/', $char)) {
                $result .= $isUpper ? mb_strtoupper($char) : mb_strtolower($char);
                $isUpper = !$isUpper;
            } else {
                $result .= $char;
            }
        }
        
        return $result;
    }

    private function convertToInverseCase(string $text): string
    {
        $result = '';
        
        for ($i = 0; $i < mb_strlen($text); $i++) {
            $char = mb_substr($text, $i, 1);
            
            if (mb_strtolower($char) !== $char) {
                $result .= mb_strtolower($char);
            } elseif (mb_strtoupper($char) !== $char) {
                $result .= mb_strtoupper($char);
            } else {
                $result .= $char;
            }
        }
        
        return $result;
    }

    public function getSupportedCases(): array
    {
        return [
            'cases' => $this->supportedCases,
            'examples' => [
                'input' => 'Hello World Example',
                'outputs' => [
                    'lower' => 'hello world example',
                    'upper' => 'HELLO WORLD EXAMPLE',
                    'title' => 'Hello World Example',
                    'sentence' => 'Hello world example',
                    'camel' => 'helloWorldExample',
                    'pascal' => 'HelloWorldExample',
                    'snake' => 'hello_world_example',
                    'kebab' => 'hello-world-example',
                    'constant' => 'HELLO_WORLD_EXAMPLE',
                    'dot' => 'hello.world.example',
                    'path' => 'hello/world/example',
                    'alternating' => 'hElLo WoRlD eXaMpLe',
                    'inverse' => 'hELLO wORLD eXAMPLE',
                ]
            ]
        ];
    }
}