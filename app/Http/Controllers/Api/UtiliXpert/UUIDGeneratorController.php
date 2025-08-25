<?php

namespace App\Http\Controllers\Api\UtiliXpert;

use App\Http\Controllers\Controller;
use App\Http\Requests\UtiliXpert\GenerateUUIDRequest;
use App\Http\Resources\UtiliXpert\UUIDResource;
use App\Services\UtiliXpert\UUIDGeneratorService;
use Illuminate\Http\JsonResponse;

class UUIDGeneratorController extends Controller
{
    public function __construct(
        private UUIDGeneratorService $uuidGeneratorService
    ) {}

    public function generate(GenerateUUIDRequest $request): UUIDResource|JsonResponse
    {
        try {
            $result = $this->uuidGeneratorService->generate(
                $request->validated('version'),
                $request->validated('count'),
                $request->validated('namespace'),
                $request->validated('name')
            );

            return new UUIDResource($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate UUID: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getSupportedOptions(): JsonResponse
    {
        $options = $this->uuidGeneratorService->getSupportedOptions();
        
        return response()->json([
            'success' => true,
            'data' => $options,
        ]);
    }
}