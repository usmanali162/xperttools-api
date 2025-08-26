<?php

namespace App\Http\Controllers\Api\TextXpert;

use App\Http\Controllers\Controller;
use App\Http\Requests\TextXpert\ConvertCaseRequest;
use App\Http\Resources\TextXpert\CaseConversionResource;
use App\Services\TextXpert\CaseConverterService;
use Illuminate\Http\JsonResponse;

class CaseConverterController extends Controller
{
    public function __construct(
        private CaseConverterService $caseConverterService
    ) {}

    public function convert(ConvertCaseRequest $request): CaseConversionResource|JsonResponse
    {
        try {
            if ($request->validated('convert_all')) {
                $result = $this->caseConverterService->convertMultipleCases(
                    $request->validated('text')
                );
            } else {
                $result = $this->caseConverterService->convertCase(
                    $request->validated('text'),
                    $request->validated('case_type', 'lower')
                );
            }

            return new CaseConversionResource($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to convert case: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getSupportedCases(): JsonResponse
    {
        $cases = $this->caseConverterService->getSupportedCases();
        
        return response()->json([
            'success' => true,
            'data' => $cases,
        ]);
    }
}