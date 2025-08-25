<?php

namespace App\Services\UtiliXpert;

use App\Services\BaseToolService;
use InvalidArgumentException;

class UnitConverterService extends BaseToolService
{
    private array $conversions = [
        'length' => [
            'meter' => 1.0,
            'kilometer' => 0.001,
            'centimeter' => 100.0,
            'millimeter' => 1000.0,
            'inch' => 39.3701,
            'foot' => 3.28084,
            'yard' => 1.09361,
            'mile' => 0.000621371,
        ],
        'weight' => [
            'gram' => 1.0,
            'kilogram' => 0.001,
            'pound' => 0.00220462,
            'ounce' => 0.035274,
            'ton' => 0.000001,
        ],
        'temperature' => [
            // Temperature needs special handling
        ],
        'area' => [
            'square_meter' => 1.0,
            'square_kilometer' => 0.000001,
            'square_foot' => 10.7639,
            'acre' => 0.000247105,
            'hectare' => 0.0001,
        ],
        'volume' => [
            'liter' => 1.0,
            'milliliter' => 1000.0,
            'gallon' => 0.264172,
            'cup' => 4.22675,
            'pint' => 2.11338,
            'quart' => 1.05669,
        ],
    ];

    public function convert(float $value, string $fromUnit, string $toUnit, string $category): array
    {
        $this->validateCategory($category);
        $this->validateUnits($fromUnit, $toUnit, $category);

        if ($category === 'temperature') {
            $result = $this->convertTemperature($value, $fromUnit, $toUnit);
        } else {
            $result = $this->convertStandardUnit($value, $fromUnit, $toUnit, $category);
        }

        $this->logUsage('unit_converter', [
            'category' => $category,
            'from' => $fromUnit,
            'to' => $toUnit,
            'value' => $value,
        ]);

        return [
            'original_value' => $value,
            'original_unit' => $fromUnit,
            'converted_value' => round($result, 8),
            'converted_unit' => $toUnit,
            'category' => $category,
            'formula' => $this->getFormula($fromUnit, $toUnit, $category),
        ];
    }

    private function convertStandardUnit(float $value, string $fromUnit, string $toUnit, string $category): float
    {
        $fromFactor = $this->conversions[$category][$fromUnit];
        $toFactor = $this->conversions[$category][$toUnit];
        
        // Convert to base unit, then to target unit
        $baseValue = $value / $fromFactor;
        return $baseValue * $toFactor;
    }

    private function convertTemperature(float $value, string $fromUnit, string $toUnit): float
    {
        // Convert to Celsius first
        $celsius = match ($fromUnit) {
            'celsius' => $value,
            'fahrenheit' => ($value - 32) * 5/9,
            'kelvin' => $value - 273.15,
            default => throw new InvalidArgumentException("Unknown temperature unit: $fromUnit"),
        };

        // Convert from Celsius to target
        return match ($toUnit) {
            'celsius' => $celsius,
            'fahrenheit' => ($celsius * 9/5) + 32,
            'kelvin' => $celsius + 273.15,
            default => throw new InvalidArgumentException("Unknown temperature unit: $toUnit"),
        };
    }

    private function validateCategory(string $category): void
    {
        if (!array_key_exists($category, $this->conversions) && $category !== 'temperature') {
            throw new InvalidArgumentException("Unsupported category: $category");
        }
    }

    private function validateUnits(string $fromUnit, string $toUnit, string $category): void
    {
        if ($category === 'temperature') {
            $validUnits = ['celsius', 'fahrenheit', 'kelvin'];
            if (!in_array($fromUnit, $validUnits) || !in_array($toUnit, $validUnits)) {
                throw new InvalidArgumentException("Invalid temperature units");
            }
        } else {
            $validUnits = array_keys($this->conversions[$category]);
            if (!in_array($fromUnit, $validUnits) || !in_array($toUnit, $validUnits)) {
                throw new InvalidArgumentException("Invalid units for category: $category");
            }
        }
    }

    private function getFormula(string $fromUnit, string $toUnit, string $category): string
    {
        if ($category === 'temperature') {
            return match ([$fromUnit, $toUnit]) {
                ['celsius', 'fahrenheit'] => '(°C × 9/5) + 32',
                ['fahrenheit', 'celsius'] => '(°F − 32) × 5/9',
                ['celsius', 'kelvin'] => '°C + 273.15',
                ['kelvin', 'celsius'] => 'K − 273.15',
                ['fahrenheit', 'kelvin'] => '(°F − 32) × 5/9 + 273.15',
                ['kelvin', 'fahrenheit'] => '(K − 273.15) × 9/5 + 32',
                default => "$fromUnit to $toUnit",
            };
        }

        return "$fromUnit to $toUnit conversion";
    }

    public function getSupportedUnits(): array
    {
        return [
            'categories' => array_keys($this->conversions) + ['temperature'],
            'units' => $this->conversions + [
                'temperature' => ['celsius', 'fahrenheit', 'kelvin']
            ],
        ];
    }
}