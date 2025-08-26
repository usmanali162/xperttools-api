<?php

namespace App\Services\UtiliXpert;

use App\Services\BaseToolService;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Picqer\Barcode\BarcodeGeneratorHTML;
use Picqer\Barcode\BarcodeGenerator;

class BarcodeGeneratorService extends BaseToolService
{
    private array $supportedTypes = [
        'CODE_128' => 'Code 128',
        'CODE_39' => 'Code 39', 
        'CODE_93' => 'Code 93',
        'CODABAR' => 'Codabar',
        'EAN_8' => 'EAN-8',
        'EAN_13' => 'EAN-13',
        'UPC_A' => 'UPC-A',
        'UPC_E' => 'UPC-E',
        'ITF_14' => 'ITF-14',
        'POSTNET' => 'POSTNET',
    ];

    private array $supportedFormats = ['png', 'svg', 'html'];

    public function generateBarcode(
        string $text,
        string $type = 'CODE_128',
        string $format = 'png',
        int $width = 2,
        int $height = 30
    ): array {
        $this->validateInput([
            'text' => $text,
            'type' => $type,
            'format' => $format,
            'width' => $width,
            'height' => $height,
        ], [
            'text' => 'required|string|max:100',
            'type' => 'required|string',
            'format' => 'required|string|in:png,svg,html',
            'width' => 'required|integer|min:1|max:10',
            'height' => 'required|integer|min:10|max:200',
        ]);

        if (!array_key_exists($type, $this->supportedTypes)) {
            throw new \InvalidArgumentException("Unsupported barcode type: {$type}");
        }

        $this->validateTextForType($text, $type);

        $barcodeData = $this->generateBarcodeData($text, $type, $format, $width, $height);

        $this->logUsage('barcode_generator', [
            'type' => $type,
            'format' => $format,
            'text_length' => strlen($text),
        ]);

        return [
            'barcode' => [
                'data_url' => $barcodeData['data_url'],
                'base64' => $barcodeData['base64'],
                'format' => $format,
                'type' => $type,
                'width' => $width,
                'height' => $height,
            ],
            'input' => [
                'text' => $text,
                'type_name' => $this->supportedTypes[$type],
            ],
            'metadata' => [
                'file_size' => strlen($barcodeData['base64']),
                'generated_at' => now()->toISOString(),
            ],
        ];
    }

    private function generateBarcodeData(string $text, string $type, string $format, int $width, int $height): array
    {
        try {
            // Clean the text
            $text = trim($text);
            
            $typeConstant = constant(BarcodeGenerator::class . '::TYPE_' . $type);

            switch ($format) {
                case 'png':
                    $generator = new BarcodeGeneratorPNG();
                    $binaryData = $generator->getBarcode($text, $typeConstant, $width, $height);
                    // Ensure proper base64 encoding for binary data
                    $base64Data = base64_encode($binaryData);
                    $dataUrl = 'data:image/png;base64,' . $base64Data;
                    // Return only the data URL, not the raw binary data
                    return [
                        'data' => null, // Don't return raw binary data
                        'data_url' => $dataUrl,
                        'base64' => $base64Data,
                    ];

                case 'svg':
                    $generator = new BarcodeGeneratorSVG();
                    $svgData = $generator->getBarcode($text, $typeConstant, $width, $height);
                    // SVG is text-based, but encode to base64 for consistency
                    $base64Data = base64_encode($svgData);
                    $dataUrl = 'data:image/svg+xml;base64,' . $base64Data;
                    return [
                        'data' => $svgData, // SVG is safe to include as text
                        'data_url' => $dataUrl,
                        'base64' => $base64Data,
                    ];

                case 'html':
                    $generator = new BarcodeGeneratorHTML();
                    $htmlData = $generator->getBarcode($text, $typeConstant, $width, $height);
                    $base64Data = base64_encode($htmlData);
                    $dataUrl = 'data:text/html;base64,' . $base64Data;
                    return [
                        'data' => $htmlData, // HTML is safe to include as text
                        'data_url' => $dataUrl,
                        'base64' => $base64Data,
                    ];

                default:
                    throw new \InvalidArgumentException("Unsupported format: {$format}");
            }
            
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Failed to generate barcode: " . $e->getMessage());
        }
    }

    private function validateTextForType(string $text, string $type): void
    {
        switch ($type) {
            case 'EAN_8':
                if (!preg_match('/^\d{7,8}$/', $text)) {
                    throw new \InvalidArgumentException('EAN-8 requires 7-8 digits only');
                }
                break;

            case 'EAN_13':
                if (!preg_match('/^\d{12,13}$/', $text)) {
                    throw new \InvalidArgumentException('EAN-13 requires 12-13 digits only');
                }
                break;

            case 'UPC_A':
                if (!preg_match('/^\d{11,12}$/', $text)) {
                    throw new \InvalidArgumentException('UPC-A requires 11-12 digits only');
                }
                break;

            case 'UPC_E':
                if (!preg_match('/^\d{6,8}$/', $text)) {
                    throw new \InvalidArgumentException('UPC-E requires 6-8 digits only');
                }
                break;

            case 'CODE_39':
                if (!preg_match('/^[A-Z0-9 \-\.\$\/\+\%]+$/', $text)) {
                    throw new \InvalidArgumentException('Code 39 supports only uppercase letters, digits, and specific symbols');
                }
                break;

            case 'CODABAR':
                if (!preg_match('/^[A-D][0-9\-\$\:\.\+\/]+[A-D]$/', $text)) {
                    throw new \InvalidArgumentException('Codabar must start and end with A, B, C, or D and contain only digits and specific symbols');
                }
                break;

            case 'POSTNET':
                if (!preg_match('/^\d{5,11}$/', $text)) {
                    throw new \InvalidArgumentException('POSTNET requires 5-11 digits only');
                }
                break;
        }
    }

    public function getSupportedOptions(): array
    {
        return [
            'types' => $this->supportedTypes,
            'formats' => $this->supportedFormats,
            'width_range' => ['min' => 1, 'max' => 10],
            'height_range' => ['min' => 10, 'max' => 200],
            'type_requirements' => [
                'EAN_8' => '7-8 digits',
                'EAN_13' => '12-13 digits',
                'UPC_A' => '11-12 digits',
                'UPC_E' => '6-8 digits',
                'CODE_39' => 'Uppercase letters, digits, and symbols: - . $ / + %',
                'CODE_128' => 'Any ASCII characters',
                'CODE_93' => 'Any ASCII characters',
                'CODABAR' => 'Start/end with A-D, digits and symbols: - $ : . + /',
                'ITF_14' => '13-14 digits',
                'POSTNET' => '5-11 digits',
            ],
        ];
    }
}