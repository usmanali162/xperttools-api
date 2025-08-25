<?php

namespace App\Http\Controllers\Api\UtiliXpert;

use App\Http\Controllers\Controller;
use App\Http\Requests\UtiliXpert\ConvertUnitRequest;
use App\Http\Resources\UtiliXpert\ConversionResource;
use App\Services\UtiliXpert\UnitConverterService;
use Illuminate\Http\JsonResponse;

class UnitConverterController extends Controller
{
    public function __construct(
        private UnitConverterService $unitConverterService
    ) {}

    public function convert(ConvertUnitRequest $request): ConversionResource
    {
        try {
            $result = $this->unitConverterService->convert(
                $request->validated('value'),
                $request->validated('from_unit'),
                $request->validated('to_unit'),
                $request->validated('category')
            );

            return new ConversionResource($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function getSupportedUnits(): JsonResponse
    {
        $units = $this->unitConverterService->getSupportedUnits();
        
        return response()->json([
            'success' => true,
            'data' => $units,
        ]);
    }
}