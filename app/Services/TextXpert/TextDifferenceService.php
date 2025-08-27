<?php

namespace App\Services\TextXpert;

use App\Services\BaseToolService;

class TextDifferenceService extends BaseToolService
{
    public function compareTexts(
        string $originalText,
        string $modifiedText,
        string $comparisonType = 'word'
    ): array {
        $this->validateInput([
            'original_text' => $originalText,
            'modified_text' => $modifiedText,
            'comparison_type' => $comparisonType,
        ], [
            'original_text' => 'required|string|max:500000',
            'modified_text' => 'required|string|max:500000',
            'comparison_type' => 'required|string|in:character,word,line',
        ]);

        $differences = match ($comparisonType) {
            'character' => $this->compareByCharacter($originalText, $modifiedText),
            'word' => $this->compareByWord($originalText, $modifiedText),
            'line' => $this->compareByLine($originalText, $modifiedText),
        };

        $this->logUsage('text_difference', [
            'comparison_type' => $comparisonType,
            'original_length' => mb_strlen($originalText),
            'modified_length' => mb_strlen($modifiedText),
        ]);

        return [
            'original_text' => $originalText,
            'modified_text' => $modifiedText,
            'comparison_type' => $comparisonType,
            'differences' => $differences,
            'statistics' => $this->calculateStatistics($originalText, $modifiedText, $differences),
            'formatted_output' => $this->formatDifferences($differences, $comparisonType),
        ];
    }

    private function compareByCharacter(string $original, string $modified): array
    {
        $originalChars = mb_str_split($original);
        $modifiedChars = mb_str_split($modified);
        
        return $this->calculateLCS($originalChars, $modifiedChars);
    }

    private function compareByWord(string $original, string $modified): array
    {
        $originalWords = $this->splitIntoWords($original);
        $modifiedWords = $this->splitIntoWords($modified);
        
        return $this->calculateLCS($originalWords, $modifiedWords);
    }

    private function compareByLine(string $original, string $modified): array
    {
        $originalLines = explode("\n", $original);
        $modifiedLines = explode("\n", $modified);
        
        return $this->calculateLCS($originalLines, $modifiedLines);
    }

    private function splitIntoWords(string $text): array
    {
        return preg_split('/(\s+|[^\w\s])/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    }

    private function calculateLCS(array $original, array $modified): array
    {
        $originalLength = count($original);
        $modifiedLength = count($modified);
        
        // Create LCS table
        $lcs = array_fill(0, $originalLength + 1, array_fill(0, $modifiedLength + 1, 0));
        
        for ($i = 1; $i <= $originalLength; $i++) {
            for ($j = 1; $j <= $modifiedLength; $j++) {
                if ($original[$i - 1] === $modified[$j - 1]) {
                    $lcs[$i][$j] = $lcs[$i - 1][$j - 1] + 1;
                } else {
                    $lcs[$i][$j] = max($lcs[$i - 1][$j], $lcs[$i][$j - 1]);
                }
            }
        }
        
        // Backtrack to find differences
        return $this->backtrackDifferences($original, $modified, $lcs);
    }

    private function backtrackDifferences(array $original, array $modified, array $lcs): array
    {
        $differences = [];
        $i = count($original);
        $j = count($modified);
        
        while ($i > 0 || $j > 0) {
            if ($i > 0 && $j > 0 && $original[$i - 1] === $modified[$j - 1]) {
                // No change
                $differences[] = [
                    'type' => 'unchanged',
                    'content' => $original[$i - 1],
                    'original_index' => $i - 1,
                    'modified_index' => $j - 1,
                ];
                $i--;
                $j--;
            } elseif ($j > 0 && ($i === 0 || $lcs[$i][$j - 1] >= $lcs[$i - 1][$j])) {
                // Addition
                $differences[] = [
                    'type' => 'added',
                    'content' => $modified[$j - 1],
                    'original_index' => null,
                    'modified_index' => $j - 1,
                ];
                $j--;
            } elseif ($i > 0 && ($j === 0 || $lcs[$i][$j - 1] < $lcs[$i - 1][$j])) {
                // Deletion
                $differences[] = [
                    'type' => 'deleted',
                    'content' => $original[$i - 1],
                    'original_index' => $i - 1,
                    'modified_index' => null,
                ];
                $i--;
            }
        }
        
        return array_reverse($differences);
    }

    private function calculateStatistics(string $original, string $modified, array $differences): array
    {
        $stats = [
            'total_changes' => 0,
            'additions' => 0,
            'deletions' => 0,
            'unchanged' => 0,
        ];

        foreach ($differences as $diff) {
            $stats[$diff['type'] === 'added' ? 'additions' : 
                   ($diff['type'] === 'deleted' ? 'deletions' : 'unchanged')]++;
        }

        $stats['total_changes'] = $stats['additions'] + $stats['deletions'];

        return array_merge($stats, [
            'original_length' => mb_strlen($original),
            'modified_length' => mb_strlen($modified),
            'similarity_percentage' => $this->calculateSimilarity($differences),
        ]);
    }

    private function calculateSimilarity(array $differences): float
    {
        $total = count($differences);
        if ($total === 0) return 100.0;
        
        $unchanged = count(array_filter($differences, fn($d) => $d['type'] === 'unchanged'));
        return round(($unchanged / $total) * 100, 2);
    }

    private function formatDifferences(array $differences, string $type): array
    {
        return [
            'html_output' => $this->formatAsHtml($differences),
            'unified_diff' => $this->formatAsUnifiedDiff($differences, $type),
            'side_by_side' => $this->formatAsSideBySide($differences),
        ];
    }

    private function formatAsHtml(array $differences): string
    {
        $html = '<div class="text-diff">';
        
        foreach ($differences as $diff) {
            $class = match ($diff['type']) {
                'added' => 'diff-added',
                'deleted' => 'diff-deleted',
                'unchanged' => 'diff-unchanged',
            };
            
            $content = htmlspecialchars($diff['content']);
            $html .= "<span class=\"{$class}\">{$content}</span>";
        }
        
        $html .= '</div>';
        return $html;
    }

    private function formatAsUnifiedDiff(array $differences, string $type): array
    {
        $lines = [];
        $currentLine = '';
        $lineType = null;
        
        foreach ($differences as $diff) {
            if ($type === 'line') {
                $prefix = match ($diff['type']) {
                    'added' => '+',
                    'deleted' => '-',
                    'unchanged' => ' ',
                };
                $lines[] = $prefix . $diff['content'];
            } else {
                if ($lineType !== $diff['type'] && $currentLine !== '') {
                    $prefix = match ($lineType) {
                        'added' => '+',
                        'deleted' => '-',
                        'unchanged' => ' ',
                    };
                    $lines[] = $prefix . $currentLine;
                    $currentLine = '';
                }
                
                $currentLine .= $diff['content'];
                $lineType = $diff['type'];
            }
        }
        
        if ($currentLine !== '') {
            $prefix = match ($lineType) {
                'added' => '+',
                'deleted' => '-',
                'unchanged' => ' ',
            };
            $lines[] = $prefix . $currentLine;
        }
        
        return $lines;
    }

    private function formatAsSideBySide(array $differences): array
    {
        $original = [];
        $modified = [];
        
        foreach ($differences as $diff) {
            switch ($diff['type']) {
                case 'unchanged':
                    $original[] = ['type' => 'unchanged', 'content' => $diff['content']];
                    $modified[] = ['type' => 'unchanged', 'content' => $diff['content']];
                    break;
                case 'deleted':
                    $original[] = ['type' => 'deleted', 'content' => $diff['content']];
                    $modified[] = ['type' => 'empty', 'content' => ''];
                    break;
                case 'added':
                    $original[] = ['type' => 'empty', 'content' => ''];
                    $modified[] = ['type' => 'added', 'content' => $diff['content']];
                    break;
            }
        }
        
        return [
            'original' => $original,
            'modified' => $modified,
        ];
    }

    public function getComparisonOptions(): array
    {
        return [
            'types' => [
                'character' => 'Compare character by character (most detailed)',
                'word' => 'Compare word by word (recommended)',
                'line' => 'Compare line by line (for structured text)',
            ],
            'features' => [
                'html_output' => 'HTML formatted differences with CSS classes',
                'unified_diff' => 'Git-style unified diff format',
                'side_by_side' => 'Side-by-side comparison view',
                'statistics' => 'Detailed change statistics and similarity percentage',
            ],
            'css_classes' => [
                'diff-added' => 'Added content (green background)',
                'diff-deleted' => 'Deleted content (red background)',  
                'diff-unchanged' => 'Unchanged content (no styling)',
            ],
        ];
    }
}