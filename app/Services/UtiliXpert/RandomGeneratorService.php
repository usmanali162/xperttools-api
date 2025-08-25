<?php

namespace App\Services\UtiliXpert;

use App\Services\BaseToolService;

class RandomGeneratorService extends BaseToolService
{
    private array $firstNames = [
        'male' => ['James', 'John', 'Robert', 'Michael', 'William', 'David', 'Richard', 'Joseph', 'Thomas', 'Christopher'],
        'female' => ['Mary', 'Patricia', 'Jennifer', 'Linda', 'Elizabeth', 'Barbara', 'Susan', 'Jessica', 'Sarah', 'Karen'],
        'unisex' => ['Alex', 'Jordan', 'Casey', 'Riley', 'Taylor', 'Morgan', 'Jamie', 'Avery', 'Quinn', 'Dakota']
    ];

    private array $lastNames = [
        'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez',
        'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin'
    ];

    public function generateNumbers(int $min, int $max, int $count = 1, bool $unique = false): array
    {
        $this->validateInput([
            'min' => $min,
            'max' => $max,
            'count' => $count,
        ], [
            'min' => 'required|integer',
            'max' => 'required|integer|gt:min',
            'count' => 'required|integer|min:1|max:1000',
        ]);

        if ($unique && ($max - $min + 1) < $count) {
            throw new \InvalidArgumentException("Cannot generate {$count} unique numbers in range {$min}-{$max}");
        }

        $numbers = [];
        $attempts = 0;
        $maxAttempts = $count * 10;

        while (count($numbers) < $count && $attempts < $maxAttempts) {
            $number = random_int($min, $max);
            
            if (!$unique || !in_array($number, $numbers)) {
                $numbers[] = $number;
            }
            $attempts++;
        }

        $this->logUsage('random_number_generator', [
            'range' => "{$min}-{$max}",
            'count' => $count,
            'unique' => $unique,
        ]);

        return [
            'numbers' => $numbers,
            'parameters' => [
                'min' => $min,
                'max' => $max,
                'count' => $count,
                'unique' => $unique,
            ],
        ];
    }

    public function generateNames(int $count = 1, string $gender = 'unisex', bool $includeLastName = true): array
    {
        $this->validateInput([
            'count' => $count,
            'gender' => $gender,
        ], [
            'count' => 'required|integer|min:1|max:100',
            'gender' => 'required|string|in:male,female,unisex',
        ]);

        $names = [];
        $availableFirstNames = $this->firstNames[$gender];

        for ($i = 0; $i < $count; $i++) {
            $firstName = $availableFirstNames[array_rand($availableFirstNames)];
            $name = $firstName;
            
            if ($includeLastName) {
                $lastName = $this->lastNames[array_rand($this->lastNames)];
                $name = "{$firstName} {$lastName}";
            }
            
            $names[] = [
                'full_name' => $name,
                'first_name' => $firstName,
                'last_name' => $includeLastName ? $lastName : null,
                'gender' => $gender,
            ];
        }

        $this->logUsage('random_name_generator', [
            'count' => $count,
            'gender' => $gender,
            'include_last_name' => $includeLastName,
        ]);

        return [
            'names' => $names,
            'parameters' => [
                'count' => $count,
                'gender' => $gender,
                'include_last_name' => $includeLastName,
            ],
        ];
    }

    public function generateString(int $length = 10, string $type = 'mixed'): array
    {
        $this->validateInput([
            'length' => $length,
            'type' => $type,
        ], [
            'length' => 'required|integer|min:1|max:1000',
            'type' => 'required|string|in:alphabetic,numeric,alphanumeric,mixed,symbols',
        ]);

        $characters = match ($type) {
            'alphabetic' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'numeric' => '0123456789',
            'alphanumeric' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
            'symbols' => '!@#$%^&*()_+-=[]{}|;:,.<>?',
            'mixed' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*',
        };

        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[random_int(0, strlen($characters) - 1)];
        }

        $this->logUsage('random_string_generator', [
            'length' => $length,
            'type' => $type,
        ]);

        return [
            'string' => $string,
            'parameters' => [
                'length' => $length,
                'type' => $type,
            ],
        ];
    }
}