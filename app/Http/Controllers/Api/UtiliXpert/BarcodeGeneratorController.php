<?php

namespace App\Http\Controllers\Api\UtiliXpert;

use App\Http\Controllers\Controller;
use App\Http\Requests\UtiliXpert\GenerateBarcodeRequest;
use App\Http\Resources\UtiliXpert\BarcodeResource;
use App\Services\UtiliXpert\BarcodeGeneratorService;
use Illuminate\Http\JsonResponse;

class BarcodeGeneratorController extends Controller
{
    public function __construct(
        private BarcodeGeneratorService $barcodeGeneratorService
    ) {}

    public function generate(GenerateBarcodeRequest $request): BarcodeResource|JsonResponse
    {
        try {
            $result = $this->barcodeGeneratorService->generateBarcode(
                $request->validated('text'),
                $request->validated('type'),
                $request->validated('format'),
                $request->validated('width'),
                $request->validated('height')
            );

            return new BarcodeResource($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate barcode: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getSupportedOptions(): JsonResponse
    {
        $options = $this->barcodeGeneratorService->getSupportedOptions();
        
        return response()->json([
            'success' => true,
            'data' => $options,
        ]);
    }
}