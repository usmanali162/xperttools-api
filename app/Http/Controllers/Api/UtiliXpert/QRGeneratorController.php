<?php

namespace App\Http\Controllers\Api\UtiliXpert;

use App\Http\Controllers\Controller;
use App\Http\Requests\UtiliXpert\GenerateQRRequest;
use App\Http\Resources\UtiliXpert\QRCodeResource;
use App\Services\UtiliXpert\QRGeneratorService;
use Illuminate\Http\JsonResponse;

class QRGeneratorController extends Controller
{
    public function __construct(
        private QRGeneratorService $qrGeneratorService
    ) {}

    public function generate(GenerateQRRequest $request): QRCodeResource|JsonResponse
    {
        try {
            $result = $this->qrGeneratorService->generate(
                $request->validated('text'),
                $request->validated('format'),
                $request->validated('size'),
                $request->validated('background_color'),
                $request->validated('foreground_color')
            );

            return new QRCodeResource($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate QR code: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getSupportedOptions(): JsonResponse
    {
        $options = $this->qrGeneratorService->getSupportedOptions();
        
        return response()->json([
            'success' => true,
            'data' => $options,
        ]);
    }
}