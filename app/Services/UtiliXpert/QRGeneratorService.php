<?php

namespace App\Services\UtiliXpert;

use App\Services\BaseToolService;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;

class QRGeneratorService extends BaseToolService
{
    private array $supportedFormats = ['png', 'svg'];
    private array $supportedSizes = [100, 150, 200, 250, 300, 400, 500];

    public function generate(
        string $text, 
        string $format = 'png', 
        int $size = 200,
        ?string $backgroundColor = null,
        ?string $foregroundColor = null
    ): array {
        $this->validateInput([
            'text' => $text,
            'format' => $format,
            'size' => $size,
        ], [
            'text' => 'required|string|max:2000',
            'format' => 'required|in:png,svg',
            'size' => 'required|integer|min:50|max:1000',
        ]);

        $qrCode = QrCode::size($size);

        // Set colors if provided
        if ($backgroundColor) {
            $qrCode->backgroundColor($backgroundColor);
        }
        if ($foregroundColor) {
            $qrCode->color($foregroundColor);
        }

        // Generate QR code
        if ($format === 'svg') {
            $qrCodeData = $qrCode->format('svg')->generate($text);
            $dataUrl = 'data:image/svg+xml;base64,' . base64_encode($qrCodeData);
        } else {
            $qrCodeData = $qrCode->format('png')->generate($text);
            $dataUrl = 'data:image/png;base64,' . base64_encode($qrCodeData);
        }

        $this->logUsage('qr_generator', [
            'text_length' => strlen($text),
            'format' => $format,
            'size' => $size,
        ]);

        return [
            'text' => $text,
            'format' => $format,
            'size' => $size,
            'data_url' => $dataUrl,
            'file_size' => strlen($qrCodeData),
            'download_name' => 'qrcode-' . Str::random(8) . '.' . $format,
        ];
    }

    public function getSupportedOptions(): array
    {
        return [
            'formats' => $this->supportedFormats,
            'sizes' => $this->supportedSizes,
            'max_text_length' => 2000,
            'features' => [
                'custom_colors' => true,
                'multiple_formats' => true,
                'high_resolution' => true,
            ],
        ];
    }
}