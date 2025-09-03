<?php

namespace App\Services\CodeXpert;

use App\Services\BaseToolService;

class RegexTesterService extends BaseToolService
{
    public function testPattern(
        string $pattern,
        string $testString,
        string $flags = '',
        bool $globalMatch = false,
        bool $explainPattern = false
    ): array {
        $this->validateInput([
            'pattern' => $pattern,
            'test_string' => $testString,
            'flags' => $flags,
            'global_match' => $globalMatch,
            'explain_pattern' => $explainPattern,
        ], [
            'pattern' => 'required|string|max:5000',
            'test_string' => 'required|string|max:1000000',
            'flags' => 'string|max:10',
            'global_match' => 'boolean',
            'explain_pattern' => 'boolean',
        ]);

        // Validate and prepare the pattern
        $validationResult = $this->validatePattern($pattern, $flags);
        if (!$validationResult['valid']) {
            return [
                'success' => false,
                'valid_pattern' => false,
                'error' => $validationResult['error'],
                'matches' => [],
                'statistics' => [],
            ];
        }

        $fullPattern = $validationResult['full_pattern'];
        
        // Perform the regex testing
        $matches = $this->performMatching($fullPattern, $testString, $globalMatch);
        
        // Generate pattern explanation if requested
        $explanation = $explainPattern ? $this->explainPattern($pattern) : null;
        
        // Calculate statistics
        $statistics = $this->calculateStatistics($pattern, $testString, $matches);

        $this->logUsage('regex_tester', [
            'pattern_length' => mb_strlen($pattern),
            'test_string_length' => mb_strlen($testString),
            'flags' => $flags,
            'global_match' => $globalMatch,
            'matches_found' => $matches['total_matches'],
        ]);

        return [
            'success' => true,
            'valid_pattern' => true,
            'pattern' => $pattern,
            'flags' => $flags,
            'test_string' => $testString,
            'matches' => $matches,
            'statistics' => $statistics,
            'explanation' => $explanation,
            'highlighted_text' => $this->highlightMatches($testString, $matches),
            'replacement_preview' => null, // Will be set if replacement is requested
        ];
    }

    public function testReplacement(
        string $pattern,
        string $testString,
        string $replacement,
        string $flags = '',
        bool $globalReplace = false
    ): array {
        $this->validateInput([
            'pattern' => $pattern,
            'test_string' => $testString,
            'replacement' => $replacement,
            'flags' => $flags,
            'global_replace' => $globalReplace,
        ], [
            'pattern' => 'required|string|max:5000',
            'test_string' => 'required|string|max:1000000',
            'replacement' => 'required|string|max:10000',
            'flags' => 'string|max:10',
            'global_replace' => 'boolean',
        ]);

        $validationResult = $this->validatePattern($pattern, $flags);
        if (!$validationResult['valid']) {
            return [
                'success' => false,
                'error' => $validationResult['error'],
                'replaced_text' => null,
            ];
        }

        $fullPattern = $validationResult['full_pattern'];
        
        // Perform replacement
        if ($globalReplace) {
            $replacedText = preg_replace($fullPattern, $replacement, $testString);
            $replacementCount = 0;
            preg_replace($fullPattern, $replacement, $testString, -1, $replacementCount);
        } else {
            $replacedText = preg_replace($fullPattern, $replacement, $testString, 1);
            $replacementCount = $replacedText !== $testString ? 1 : 0;
        }

        if ($replacedText === null) {
            return [
                'success' => false,
                'error' => 'Replacement failed: ' . $this->getPregError(),
                'replaced_text' => null,
            ];
        }

        return [
            'success' => true,
            'original_text' => $testString,
            'replaced_text' => $replacedText,
            'replacement_count' => $replacementCount,
            'diff' => $this->generateDiff($testString, $replacedText),
        ];
    }

    private function validatePattern(string $pattern, string $flags): array
    {
        // Build the full pattern with delimiters and flags
        $delimiter = $this->findSafeDelimiter($pattern);
        $modifiers = $this->parseFlags($flags);
        
        // Escape the delimiter in the pattern if it exists
        $escapedPattern = str_replace($delimiter, '\\' . $delimiter, $pattern);
        $fullPattern = $delimiter . $escapedPattern . $delimiter . $modifiers;

        // Test if the pattern is valid
        $oldErrorLevel = error_reporting(0);
        $result = @preg_match($fullPattern, '');
        $error = error_get_last();
        error_reporting($oldErrorLevel);

        if ($result === false) {
            $errorMessage = $error ? $error['message'] : 'Invalid regex pattern';
            $errorMessage = $this->cleanErrorMessage($errorMessage);
            
            return [
                'valid' => false,
                'error' => $errorMessage,
                'full_pattern' => null,
            ];
        }

        return [
            'valid' => true,
            'error' => null,
            'full_pattern' => $fullPattern,
        ];
    }

    private function findSafeDelimiter(string $pattern): string
    {
        $delimiters = ['/', '#', '~', '@', '!', '%', '`'];
        
        foreach ($delimiters as $delimiter) {
            if (strpos($pattern, $delimiter) === false) {
                return $delimiter;
            }
        }
        
        // If all delimiters exist in the pattern, use / and it will be escaped
        return '/';
    }

    private function parseFlags(string $flags): string
    {
        $validFlags = [
            'i' => 'i', // Case insensitive
            'm' => 'm', // Multiline
            's' => 's', // Single line (dot matches newline)
            'x' => 'x', // Ignore whitespace
            'u' => 'u', // Unicode
            'A' => 'A', // Anchor to start
            'D' => 'D', // Dollar end only
            'U' => 'U', // Ungreedy
        ];

        $modifiers = '';
        foreach (str_split(strtolower($flags)) as $flag) {
            if (isset($validFlags[$flag])) {
                $modifiers .= $validFlags[$flag];
            }
        }

        return $modifiers;
    }

    private function performMatching(string $pattern, string $testString, bool $globalMatch): array
    {
        if ($globalMatch) {
            preg_match_all($pattern, $testString, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        } else {
            preg_match($pattern, $testString, $singleMatch, PREG_OFFSET_CAPTURE);
            $matches = $singleMatch ? [$singleMatch] : [];
        }

        $formattedMatches = [];
        foreach ($matches as $matchSet) {
            $match = [
                'full_match' => $matchSet[0][0],
                'position' => $matchSet[0][1],
                'length' => mb_strlen($matchSet[0][0]),
                'groups' => [],
            ];

            // Process capture groups
            for ($i = 1; $i < count($matchSet); $i++) {
                if (isset($matchSet[$i])) {
                    $match['groups'][] = [
                        'group_number' => $i,
                        'value' => $matchSet[$i][0],
                        'position' => $matchSet[$i][1],
                    ];
                }
            }

            $formattedMatches[] = $match;
        }

        return [
            'total_matches' => count($formattedMatches),
            'matches' => $formattedMatches,
        ];
    }

    private function explainPattern(string $pattern): array
    {
        $explanations = [];
        $components = $this->parsePatternComponents($pattern);
        
        foreach ($components as $component) {
            $explanations[] = [
                'component' => $component['pattern'],
                'explanation' => $this->getComponentExplanation($component),
                'type' => $component['type'],
            ];
        }

        return [
            'components' => $explanations,
            'summary' => $this->generatePatternSummary($pattern),
            'common_use_cases' => $this->getCommonUseCases($pattern),
        ];
    }

    private function parsePatternComponents(string $pattern): array
    {
        $components = [];
        $current = '';
        $inCharClass = false;
        $escapeNext = false;
        
        for ($i = 0; $i < strlen($pattern); $i++) {
            $char = $pattern[$i];
            
            if ($escapeNext) {
                $current .= $char;
                $escapeNext = false;
                continue;
            }
            
            if ($char === '\\') {
                $escapeNext = true;
                $current .= $char;
                continue;
            }
            
            if ($char === '[') {
                if ($current !== '') {
                    $components[] = ['pattern' => $current, 'type' => 'literal'];
                    $current = '';
                }
                $inCharClass = true;
                $current .= $char;
            } elseif ($char === ']' && $inCharClass) {
                $current .= $char;
                $components[] = ['pattern' => $current, 'type' => 'character_class'];
                $current = '';
                $inCharClass = false;
            } elseif (!$inCharClass && in_array($char, ['^', '$', '.', '*', '+', '?', '|', '(', ')', '{', '}'])) {
                if ($current !== '') {
                    $components[] = ['pattern' => $current, 'type' => 'literal'];
                    $current = '';
                }
                $components[] = ['pattern' => $char, 'type' => $this->getMetacharType($char)];
            } else {
                $current .= $char;
            }
        }
        
        if ($current !== '') {
            $components[] = ['pattern' => $current, 'type' => $inCharClass ? 'character_class' : 'literal'];
        }
        
        return $components;
    }

    private function getMetacharType(string $char): string
    {
        return match ($char) {
            '^' => 'anchor_start',
            '$' => 'anchor_end',
            '.' => 'any_character',
            '*' => 'quantifier_zero_or_more',
            '+' => 'quantifier_one_or_more',
            '?' => 'quantifier_zero_or_one',
            '|' => 'alternation',
            '(' => 'group_open',
            ')' => 'group_close',
            '{' => 'quantifier_range_open',
            '}' => 'quantifier_range_close',
            default => 'metachar',
        };
    }

    private function getComponentExplanation(array $component): string
    {
        $pattern = $component['pattern'];
        $type = $component['type'];
        
        $explanations = [
            'anchor_start' => 'Start of line/string',
            'anchor_end' => 'End of line/string',
            'any_character' => 'Any character (except newline)',
            'quantifier_zero_or_more' => 'Zero or more of the preceding element',
            'quantifier_one_or_more' => 'One or more of the preceding element',
            'quantifier_zero_or_one' => 'Zero or one of the preceding element',
            'alternation' => 'OR operator',
            'group_open' => 'Start of capturing group',
            'group_close' => 'End of capturing group',
            'quantifier_range_open' => 'Start of quantifier range',
            'quantifier_range_close' => 'End of quantifier range',
        ];

        if (isset($explanations[$type])) {
            return $explanations[$type];
        }

        if ($type === 'character_class') {
            return $this->explainCharacterClass($pattern);
        }

        if ($type === 'literal') {
            return "Literal text: '$pattern'";
        }

        // Handle escape sequences
        if (str_starts_with($pattern, '\\')) {
            return $this->explainEscapeSequence($pattern);
        }

        return "Pattern component: $pattern";
    }

    private function explainCharacterClass(string $pattern): string
    {
        $pattern = trim($pattern, '[]');
        
        if ($pattern === '^') {
            return 'Not in character class';
        }
        
        $common = [
            'a-z' => 'lowercase letters',
            'A-Z' => 'uppercase letters',
            '0-9' => 'digits',
            'a-zA-Z' => 'letters',
            'a-zA-Z0-9' => 'alphanumeric characters',
        ];
        
        foreach ($common as $class => $description) {
            if (str_contains($pattern, $class)) {
                return "Character class: $description";
            }
        }
        
        return "Character class: any of [$pattern]";
    }

    private function explainEscapeSequence(string $sequence): string
    {
        $escapes = [
            '\\d' => 'Any digit (0-9)',
            '\\D' => 'Any non-digit',
            '\\w' => 'Any word character (a-z, A-Z, 0-9, _)',
            '\\W' => 'Any non-word character',
            '\\s' => 'Any whitespace character',
            '\\S' => 'Any non-whitespace character',
            '\\b' => 'Word boundary',
            '\\B' => 'Non-word boundary',
            '\\n' => 'Newline',
            '\\r' => 'Carriage return',
            '\\t' => 'Tab',
        ];
        
        return $escapes[$sequence] ?? "Escape sequence: $sequence";
    }

    private function generatePatternSummary(string $pattern): string
    {
        $summary = "This pattern ";
        
        if (str_starts_with($pattern, '^')) {
            $summary .= "matches from the start of the line ";
        }
        
        if (str_ends_with($pattern, '$')) {
            $summary .= "to the end of the line ";
        }
        
        if (str_contains($pattern, '\\d')) {
            $summary .= "and includes digit matching ";
        }
        
        if (str_contains($pattern, '\\w')) {
            $summary .= "and includes word character matching ";
        }
        
        if (str_contains($pattern, '+') || str_contains($pattern, '*')) {
            $summary .= "with repetition quantifiers ";
        }
        
        return rtrim($summary) ?: "This pattern matches specific text patterns";
    }

    private function getCommonUseCases(string $pattern): array
    {
        $useCases = [];
        
        // Email-like pattern
        if (str_contains($pattern, '@') || (str_contains($pattern, '\\w') && str_contains($pattern, '\\.'))) {
            $useCases[] = 'Email validation';
        }
        
        // URL-like pattern
        if (str_contains($pattern, 'http') || str_contains($pattern, 'www')) {
            $useCases[] = 'URL matching';
        }
        
        // Phone number pattern
        if (str_contains($pattern, '\\d') && (str_contains($pattern, '-') || str_contains($pattern, '('))) {
            $useCases[] = 'Phone number matching';
        }
        
        // Date pattern
        if (preg_match('/\d{1,4}.*\d{1,2}.*\d{1,4}/', $pattern)) {
            $useCases[] = 'Date matching';
        }
        
        return $useCases ?: ['General text pattern matching'];
    }

    private function highlightMatches(string $text, array $matchData): string
    {
        if (empty($matchData['matches'])) {
            return $text;
        }
        
        $highlighted = $text;
        $offset = 0;
        
        foreach ($matchData['matches'] as $match) {
            $startTag = '<mark>';
            $endTag = '</mark>';
            $position = $match['position'] + $offset;
            
            $highlighted = substr_replace(
                $highlighted,
                $startTag . $match['full_match'] . $endTag,
                $position,
                strlen($match['full_match'])
            );
            
            $offset += strlen($startTag) + strlen($endTag);
        }
        
        return $highlighted;
    }

    private function calculateStatistics(string $pattern, string $testString, array $matches): array
    {
        $textLength = mb_strlen($testString);
        $totalMatches = $matches['total_matches'];
        $matchedLength = 0;
        
        foreach ($matches['matches'] as $match) {
            $matchedLength += $match['length'];
        }
        
        return [
            'pattern_length' => mb_strlen($pattern),
            'text_length' => $textLength,
            'total_matches' => $totalMatches,
            'matched_characters' => $matchedLength,
            'coverage_percentage' => $textLength > 0 
                ? round(($matchedLength / $textLength) * 100, 2) 
                : 0,
            'average_match_length' => $totalMatches > 0 
                ? round($matchedLength / $totalMatches, 2) 
                : 0,
        ];
    }

    private function generateDiff(string $original, string $modified): array
    {
        $originalLines = explode("\n", $original);
        $modifiedLines = explode("\n", $modified);
        
        $diff = [];
        $maxLines = max(count($originalLines), count($modifiedLines));
        
        for ($i = 0; $i < $maxLines; $i++) {
            $originalLine = $originalLines[$i] ?? '';
            $modifiedLine = $modifiedLines[$i] ?? '';
            
            if ($originalLine !== $modifiedLine) {
                $diff[] = [
                    'line' => $i + 1,
                    'original' => $originalLine,
                    'modified' => $modifiedLine,
                    'type' => $originalLine === '' ? 'added' : ($modifiedLine === '' ? 'removed' : 'modified'),
                ];
            }
        }
        
        return $diff;
    }

    private function cleanErrorMessage(string $message): string
    {
        // Remove PHP function names and make errors more user-friendly
        $message = preg_replace('/preg_\w+\(\): /', '', $message);
        $message = preg_replace('/Compilation failed: /', '', $message);
        
        return $message;
    }

    private function getPregError(): string
    {
        $errors = [
            PREG_NO_ERROR => 'No error',
            PREG_INTERNAL_ERROR => 'Internal PCRE error',
            PREG_BACKTRACK_LIMIT_ERROR => 'Backtrack limit exhausted',
            PREG_RECURSION_LIMIT_ERROR => 'Recursion limit exhausted',
            PREG_BAD_UTF8_ERROR => 'Malformed UTF-8 data',
            PREG_BAD_UTF8_OFFSET_ERROR => 'Bad UTF-8 offset',
        ];
        
        $code = preg_last_error();
        return $errors[$code] ?? 'Unknown error';
    }

    public function getOptions(): array
    {
        return [
            'supported_flags' => [
                'i' => 'Case insensitive',
                'm' => 'Multiline mode (^ and $ match line breaks)',
                's' => 'Single line mode (. matches newlines)',
                'x' => 'Ignore whitespace and comments',
                'u' => 'Unicode mode',
                'A' => 'Anchor to start of string',
                'D' => 'Dollar matches end of string only',
                'U' => 'Ungreedy (lazy) quantifiers',
            ],
            'common_patterns' => [
                'email' => [
                    'pattern' => '^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}$',
                    'description' => 'Email address',
                ],
                'url' => [
                    'pattern' => 'https?://[^\\s]+',
                    'description' => 'URL',
                ],
                'phone_us' => [
                    'pattern' => '\\(\\d{3}\\) \\d{3}-\\d{4}',
                    'description' => 'US phone number',
                ],
                'ipv4' => [
                    'pattern' => '\\b(?:[0-9]{1,3}\\.){3}[0-9]{1,3}\\b',
                    'description' => 'IPv4 address',
                ],
                'date' => [
                    'pattern' => '\\d{4}-\\d{2}-\\d{2}',
                    'description' => 'Date (YYYY-MM-DD)',
                ],
                'hex_color' => [
                    'pattern' => '#[0-9A-Fa-f]{6}',
                    'description' => 'Hex color code',
                ],
            ],
            'quick_reference' => [
                'anchors' => ['^' => 'Start', '$' => 'End', '\\b' => 'Word boundary'],
                'quantifiers' => ['*' => '0 or more', '+' => '1 or more', '?' => '0 or 1', '{n,m}' => 'n to m times'],
                'character_classes' => ['\\d' => 'Digit', '\\w' => 'Word char', '\\s' => 'Whitespace', '.' => 'Any char'],
                'groups' => ['()' => 'Capturing group', '(?:)' => 'Non-capturing', '(?=)' => 'Positive lookahead'],
            ],
        ];
    }
}