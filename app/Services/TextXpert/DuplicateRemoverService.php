<?php

namespace App\Services\TextXpert;

use App\Services\BaseToolService;

class DuplicateRemoverService extends BaseToolService
{
    public function removeDuplicates(
        string $text,
        bool $caseSensitive = true,
        bool $trimWhitespace = true,
        bool $ignoreEmptyLines = true,
        string $sortOrder = 'preserve'
    ): array {
        $this->validateInput([
            'text' => $text,
            'case_sensitive' => $caseSensitive,
            'trim_whitespace' => $trimWhitespace,
            'ignore_empty_lines' => $ignoreEmptyLines,
            'sort_order' => $sortOrder,
        ], [
            'text' => 'required|string|max:1000000',
            'case_sensitive' => 'boolean',
            'trim_whitespace' => 'boolean',
            'ignore_empty_lines' => 'boolean',
            'sort_order' => 'string|in:preserve,alphabetical,reverse_alphabetical',
        ]);

        $lines = explode("\n", $text);
        $processedLines = $this->processLines($lines, $caseSensitive, $trimWhitespace, $ignoreEmptyLines);
        
        $duplicateAnalysis = $this->analyzeDuplicates($processedLines['processed'], $processedLines['original_map']);
        
        $uniqueLines = $this->getUniqueLines($processedLines['processed'], $sortOrder);
        
        $cleanText = implode("\n", $uniqueLines);

        $this->logUsage('duplicate_remover', [
            'original_lines' => count($lines),
            'unique_lines' => count($uniqueLines),
            'duplicates_removed' => count($lines) - count($uniqueLines),
            'case_sensitive' => $caseSensitive,
        ]);

        return [
            'original_text' => $text,
            'cleaned_text' => $cleanText,
            'options' => [
                'case_sensitive' => $caseSensitive,
                'trim_whitespace' => $trimWhitespace,
                'ignore_empty_lines' => $ignoreEmptyLines,
                'sort_order' => $sortOrder,
            ],
            'statistics' => [
                'original_line_count' => count($lines),
                'unique_line_count' => count($uniqueLines),
                'duplicate_lines_removed' => count($lines) - count($uniqueLines),
                'reduction_percentage' => $this->calculateReductionPercentage(count($lines), count($uniqueLines)),
                'original_character_count' => mb_strlen($text),
                'cleaned_character_count' => mb_strlen($cleanText),
            ],
            'duplicate_analysis' => $duplicateAnalysis,
            'preview' => [
                'first_10_lines' => array_slice($uniqueLines, 0, 10),
                'last_10_lines' => count($uniqueLines) > 10 ? array_slice($uniqueLines, -10) : [],
            ],
        ];
    }

    private function processLines(array $lines, bool $caseSensitive, bool $trimWhitespace, bool $ignoreEmptyLines): array
    {
        $processed = [];
        $originalMap = []; // Maps processed line back to original
        
        foreach ($lines as $index => $line) {
            $originalLine = $line;
            $processedLine = $line;
            
            if ($trimWhitespace) {
                $processedLine = trim($processedLine);
            }
            
            if ($ignoreEmptyLines && $processedLine === '') {
                continue;
            }
            
            if (!$caseSensitive) {
                $comparisonKey = mb_strtolower($processedLine);
            } else {
                $comparisonKey = $processedLine;
            }
            
            $processed[] = [
                'original' => $originalLine,
                'processed' => $processedLine,
                'comparison_key' => $comparisonKey,
                'original_index' => $index,
            ];
            
            $originalMap[] = $originalLine;
        }
        
        return [
            'processed' => $processed,
            'original_map' => $originalMap,
        ];
    }

    private function analyzeDuplicates(array $processedLines, array $originalMap): array
    {
        $duplicates = [];
        $seen = [];
        $duplicateGroups = [];
        
        foreach ($processedLines as $lineData) {
            $key = $lineData['comparison_key'];
            
            if (isset($seen[$key])) {
                if (!isset($duplicates[$key])) {
                    $duplicates[$key] = [
                        'line' => $lineData['processed'],
                        'count' => 1,
                        'positions' => [$seen[$key]],
                    ];
                }
                $duplicates[$key]['count']++;
                $duplicates[$key]['positions'][] = $lineData['original_index'];
            } else {
                $seen[$key] = $lineData['original_index'];
            }
        }
        
        // Group duplicates by frequency
        foreach ($duplicates as $key => $data) {
            $count = $data['count'];
            if (!isset($duplicateGroups[$count])) {
                $duplicateGroups[$count] = [];
            }
            $duplicateGroups[$count][] = [
                'line' => $data['line'],
                'occurrences' => $count,
                'positions' => $data['positions'],
            ];
        }
        
        // Sort groups by frequency (highest first)
        krsort($duplicateGroups);
        
        return [
            'total_duplicate_groups' => count($duplicates),
            'duplicate_groups' => $duplicateGroups,
            'most_duplicated' => $this->getMostDuplicated($duplicates),
        ];
    }

    private function getMostDuplicated(array $duplicates): ?array
    {
        if (empty($duplicates)) {
            return null;
        }
        
        $maxCount = 0;
        $mostDuplicated = null;
        
        foreach ($duplicates as $key => $data) {
            if ($data['count'] > $maxCount) {
                $maxCount = $data['count'];
                $mostDuplicated = [
                    'line' => $data['line'],
                    'occurrences' => $data['count'],
                    'positions' => $data['positions'],
                ];
            }
        }
        
        return $mostDuplicated;
    }

    private function getUniqueLines(array $processedLines, string $sortOrder): array
    {
        $unique = [];
        $seen = [];
        
        foreach ($processedLines as $lineData) {
            $key = $lineData['comparison_key'];
            
            if (!isset($seen[$key])) {
                $unique[] = $lineData['processed'];
                $seen[$key] = true;
            }
        }
        
        return match ($sortOrder) {
            'alphabetical' => $this->sortAlphabetically($unique),
            'reverse_alphabetical' => $this->sortReverseAlphabetically($unique),
            'preserve' => $unique,
            default => $unique,
        };
    }

    private function sortAlphabetically(array $lines): array
    {
        usort($lines, function ($a, $b) {
            return mb_strtolower($a) <=> mb_strtolower($b);
        });
        return $lines;
    }

    private function sortReverseAlphabetically(array $lines): array
    {
        usort($lines, function ($a, $b) {
            return mb_strtolower($b) <=> mb_strtolower($a);
        });
        return $lines;
    }

    private function calculateReductionPercentage(int $original, int $unique): float
    {
        if ($original === 0) return 0.0;
        return round((($original - $unique) / $original) * 100, 2);
    }

    public function getRemovalOptions(): array
    {
        return [
            'options' => [
                'case_sensitive' => [
                    'description' => 'Whether to treat lines with different cases as different',
                    'default' => true,
                    'example' => '"Hello" vs "hello" - treated as different if true',
                ],
                'trim_whitespace' => [
                    'description' => 'Remove leading and trailing whitespace before comparison',
                    'default' => true,
                    'example' => '" Hello " becomes "Hello"',
                ],
                'ignore_empty_lines' => [
                    'description' => 'Remove empty lines from the output',
                    'default' => true,
                    'example' => 'Blank lines are not included in result',
                ],
                'sort_order' => [
                    'description' => 'How to order the unique lines in output',
                    'default' => 'preserve',
                    'options' => [
                        'preserve' => 'Keep original order (first occurrence)',
                        'alphabetical' => 'Sort A-Z (case insensitive)',
                        'reverse_alphabetical' => 'Sort Z-A (case insensitive)',
                    ],
                ],
            ],
            'use_cases' => [
                'Email lists cleanup',
                'Remove duplicate URLs',
                'Clean up CSV data',
                'Merge multiple lists',
                'Code cleanup (imports, includes)',
            ],
        ];
    }
}