<?php

namespace App\Http\Controllers\Api\UtiliXpert;

use App\Http\Controllers\Controller;
use App\Http\Requests\UtiliXpert\GenerateRandomRequest;
use App\Services\UtiliXpert\RandomGeneratorService;
use Illuminate\Http\JsonResponse;

class RandomGeneratorController extends Controller
{
    public function __construct(
        private RandomGeneratorService $randomGeneratorService
    ) {}

    public function generate(GenerateRandomRequest $request): JsonResponse
    {
        try {
            $type = $request->validated('generator_type');
            
            $result = match ($type) {
                'number' => $this->randomGeneratorService->generateNumbers(
                    $request->validated('min'),
                    $request->validated('max'),
                    $request->validated('count'),
                    $request->validated('unique')
                ),
                'name' => $this->randomGeneratorService->generateNames(
                    $request->validated('count'),
                    $request->validated('gender'),
                    $request->validated('include_last_name')
                ),
                'string' => $this->randomGeneratorService->generateString(
                    $request->validated('length'),
                    $request->validated('string_type')
                ),
            };

            return response()->json([
                'success' => true,
                'data' => $result,
                'generated_at' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate random data: ' . $e->getMessage(),
            ], 500);
        }
    }
}