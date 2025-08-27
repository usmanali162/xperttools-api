<?php

namespace App\Services\TextXpert;

use App\Services\BaseToolService;

class LoremIpsumService extends BaseToolService
{
    private array $classicWords = [
        'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit',
        'sed', 'do', 'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et', 'dolore',
        'magna', 'aliqua', 'enim', 'ad', 'minim', 'veniam', 'quis', 'nostrud',
        'exercitation', 'ullamco', 'laboris', 'nisi', 'aliquip', 'ex', 'ea', 'commodo',
        'consequat', 'duis', 'aute', 'irure', 'in', 'reprehenderit', 'voluptate',
        'velit', 'esse', 'cillum', 'fugiat', 'nulla', 'pariatur', 'excepteur', 'sint',
        'occaecat', 'cupidatat', 'non', 'proident', 'sunt', 'culpa', 'qui', 'officia',
        'deserunt', 'mollit', 'anim', 'id', 'est', 'laborum', 'at', 'vero', 'eos',
        'accusamus', 'accusantium', 'doloremque', 'laudantium', 'totam', 'rem',
        'aperiam', 'eaque', 'ipsa', 'quae', 'ab', 'illo', 'inventore', 'veritatis',
        'et', 'quasi', 'architecto', 'beatae', 'vitae', 'dicta', 'explicabo', 'nemo',
        'ipsam', 'voluptatem', 'quia', 'voluptas', 'aspernatur', 'aut', 'odit',
        'fugit', 'consequuntur', 'magni', 'dolores', 'ratione', 'sequi', 'nesciunt'
    ];

    private string $classicStart = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit';


    public function generateText(
        string $type = 'paragraphs',
        int $count = 3,
        bool $startWithClassic = true
    ): array {
        $this->validateInput([
            'type' => $type,
            'count' => $count,
            'start_with_classic' => $startWithClassic,
        ], [
            'type' => 'required|string|in:words,sentences,paragraphs',
            'count' => 'required|integer|min:1|max:1000',
            'start_with_classic' => 'boolean',
        ]);

        $result = match ($type) {
            'words' => $this->generateWords($count, $startWithClassic),
            'sentences' => $this->generateSentences($count, $startWithClassic),
            'paragraphs' => $this->generateParagraphs($count, $startWithClassic),
        };

        $this->logUsage('lorem_ipsum', [
            'type' => $type,
            'count' => $count,
            'start_with_classic' => $startWithClassic,
        ]);

        return [
            'generated_text' => $result['text'],
            'parameters' => [
                'type' => $type,
                'count' => $count,
                'start_with_classic' => $startWithClassic,
            ],
            'statistics' => [
                'total_characters' => mb_strlen($result['text']),
                'total_words' => $result['word_count'],
                'total_sentences' => $result['sentence_count'],
                'total_paragraphs' => $result['paragraph_count'],
            ],
            'formatted_output' => [
                'plain_text' => $result['text'],
                'html_paragraphs' => $this->formatAsHtml($result['text']),
                'array_format' => $this->formatAsArray($result['text'], $type),
            ],
        ];
    }

    private function generateWords(int $count, bool $startWithClassic): array
    {
        $words = [];
        $wordCount = 0;

        if ($startWithClassic && $count >= 5) {
            $classicWords = explode(' ', $this->classicStart);
            $wordsToTake = min($count, count($classicWords));
            $words = array_slice($classicWords, 0, $wordsToTake);
            $count -= count($words);
            $wordCount += count($words);
        }

        for ($i = 0; $i < $count; $i++) {
            $words[] = $this->classicWords[array_rand($this->classicWords)];
            $wordCount++;
        }

        $text = implode(' ', $words) . '.';

        return [
            'text' => ucfirst($text),
            'word_count' => $wordCount,
            'sentence_count' => 1,
            'paragraph_count' => 1,
        ];
    }

    private function generateSentences(int $count, bool $startWithClassic): array
    {
        $sentences = [];
        $totalWords = 0;

        for ($i = 0; $i < $count; $i++) {
            if ($i === 0 && $startWithClassic) {
                $sentence = $this->classicStart;
            } else {
                $wordCount = rand(8, 20);
                $sentenceWords = [];
                
                for ($j = 0; $j < $wordCount; $j++) {
                    $sentenceWords[] = $this->classicWords[array_rand($this->classicWords)];
                }
                
                $sentence = implode(' ', $sentenceWords);
            }
            
            $sentence = ucfirst($sentence) . '.';
            $sentences[] = $sentence;
            $totalWords += count(explode(' ', $sentence));
        }

        $text = implode(' ', $sentences);

        return [
            'text' => $text,
            'word_count' => $totalWords,
            'sentence_count' => $count,
            'paragraph_count' => 1,
        ];
    }

    private function generateParagraphs(int $count, bool $startWithClassic): array
    {
        $paragraphs = [];
        $totalWords = 0;
        $totalSentences = 0;

        for ($i = 0; $i < $count; $i++) {
            $sentenceCount = rand(4, 8);
            $paragraphSentences = [];

            for ($j = 0; $j < $sentenceCount; $j++) {
                if ($i === 0 && $j === 0 && $startWithClassic) {
                    $sentence = $this->classicStart;
                } else {
                    $wordCount = rand(8, 20);
                    $sentenceWords = [];
                    
                    for ($k = 0; $k < $wordCount; $k++) {
                        $sentenceWords[] = $this->classicWords[array_rand($this->classicWords)];
                    }
                    
                    $sentence = implode(' ', $sentenceWords);
                }
                
                $sentence = ucfirst($sentence) . '.';
                $paragraphSentences[] = $sentence;
                $totalWords += count(explode(' ', $sentence));
                $totalSentences++;
            }

            $paragraphs[] = implode(' ', $paragraphSentences);
        }

        $text = implode("\n\n", $paragraphs);

        return [
            'text' => $text,
            'word_count' => $totalWords,
            'sentence_count' => $totalSentences,
            'paragraph_count' => $count,
        ];
    }

    private function formatAsHtml(string $text): string
    {
        $paragraphs = explode("\n\n", $text);
        $htmlParagraphs = array_map(fn($p) => "<p>$p</p>", $paragraphs);
        return implode("\n", $htmlParagraphs);
    }

    private function formatAsArray(string $text, string $type): array
    {
        return match ($type) {
            'words' => explode(' ', trim($text, '.')),
            'sentences' => array_map('trim', explode('.', trim($text, '.'))),
            'paragraphs' => explode("\n\n", $text),
        };
    }

    public function getGenerationOptions(): array
    {
        return [
            'types' => [
                'words' => 'Generate specific number of words',
                'sentences' => 'Generate specific number of sentences',
                'paragraphs' => 'Generate specific number of paragraphs',
            ],
            'limits' => [
                'min_count' => 1,
                'max_count' => 1000,
            ],
            'options' => [
                'start_with_classic' => 'Start with classic "Lorem ipsum dolor sit amet..."',
            ],
            'examples' => [
                'words_5' => 'Lorem ipsum dolor sit amet.',
                'sentences_2' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore.',
                'paragraphs_1' => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.\n\nUt enim ad minim veniam, quis nostrud exercitation."
            ]
        ];
    }
}