<?php

namespace App\Services\TextXpert;

use App\Services\BaseToolService;

class TextAnalyzerService extends BaseToolService
{
    public function analyzeText(string $text): array
    {
        $this->validateInput(['text' => $text], [
            'text' => 'required|string|max:1000000', // 1MB max
        ]);

        $analysis = [
            'characters' => [
                'total' => mb_strlen($text),
                'with_spaces' => mb_strlen($text),
                'without_spaces' => mb_strlen(preg_replace('/\s/', '', $text)),
                'alphabetic' => mb_strlen(preg_replace('/[^a-zA-Z]/', '', $text)),
                'numeric' => mb_strlen(preg_replace('/[^0-9]/', '', $text)),
                'special' => mb_strlen(preg_replace('/[a-zA-Z0-9\s]/', '', $text)),
            ],
            'words' => $this->analyzeWords($text),
            'lines' => $this->analyzeLines($text),
            'paragraphs' => $this->analyzeParagraphs($text),
            'sentences' => $this->analyzeSentences($text),
            'reading_stats' => $this->calculateReadingStats($text),
        ];

        $this->logUsage('text_analyzer', [
            'character_count' => $analysis['characters']['total'],
            'word_count' => $analysis['words']['total'],
        ]);

        return $analysis;
    }

    private function analyzeWords(string $text): array
    {
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $wordCount = count($words);
        
        if ($wordCount === 0) {
            return [
                'total' => 0,
                'unique' => 0,
                'average_length' => 0,
                'longest' => null,
                'shortest' => null,
            ];
        }

        $uniqueWords = array_unique(array_map('mb_strtolower', $words));
        $wordLengths = array_map('mb_strlen', $words);
        
        return [
            'total' => $wordCount,
            'unique' => count($uniqueWords),
            'average_length' => round(array_sum($wordLengths) / $wordCount, 2),
            'longest' => $words[array_keys($wordLengths, max($wordLengths))[0]],
            'shortest' => $words[array_keys($wordLengths, min($wordLengths))[0]],
        ];
    }

    private function analyzeLines(string $text): array
    {
        $lines = explode("\n", $text);
        $nonEmptyLines = array_filter($lines, fn($line) => trim($line) !== '');
        
        return [
            'total' => count($lines),
            'non_empty' => count($nonEmptyLines),
            'empty' => count($lines) - count($nonEmptyLines),
        ];
    }

    private function analyzeParagraphs(string $text): array
    {
        $paragraphs = preg_split('/\n\s*\n/', trim($text));
        $paragraphs = array_filter($paragraphs, fn($p) => trim($p) !== '');
        
        return [
            'total' => count($paragraphs),
        ];
    }

    private function analyzeSentences(string $text): array
    {
        $sentences = preg_split('/[.!?]+/', $text);
        $sentences = array_filter($sentences, fn($s) => trim($s) !== '');
        
        return [
            'total' => count($sentences),
        ];
    }

    private function calculateReadingStats(string $text): array
    {
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $wordCount = count($words);
        
        if ($wordCount === 0) {
            return [
                'reading_time_slow' => 0,
                'reading_time_average' => 0,
                'reading_time_fast' => 0,
                'speaking_time' => 0,
            ];
        }

        // Reading speeds (words per minute)
        $readingSpeedSlow = 200;
        $readingSpeedAverage = 250;
        $readingSpeedFast = 300;
        $speakingSpeed = 150;

        return [
            'reading_time_slow' => round($wordCount / $readingSpeedSlow, 1),
            'reading_time_average' => round($wordCount / $readingSpeedAverage, 1),
            'reading_time_fast' => round($wordCount / $readingSpeedFast, 1),
            'speaking_time' => round($wordCount / $speakingSpeed, 1),
        ];
    }
}