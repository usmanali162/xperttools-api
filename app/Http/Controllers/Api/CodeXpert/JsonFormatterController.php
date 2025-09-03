<?php

namespace App\Http\Controllers\Api\CodeXpert;

use App\Http\Controllers\Controller;
use App\Http\Requests\CodeXpert\FormatJsonRequest;
use App\Http\Resources\CodeXpert\JsonFormatterResource;
use App\Services\CodeXpert\JsonFormatterService;
use Illuminate\Http\JsonResponse;

class JsonFormatterController extends Controller
{
    public function __construct(
        private JsonFormatterService $jsonFormatterService
    ) {}

    public function format(FormatJsonRequest $request): JsonFormatterResource|JsonResponse
    {
        try {
            $result = $this->jsonFormatterService->formatJson(
                $request->validated('input'),
                $request->validated('indent_size'),
                $request->validated('sort_keys'),
                $request->validated('validate_only')
            );

            return new JsonFormatterResource($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process JSON: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getOptions(): JsonResponse
    {
        $options = $this->jsonFormatterService->getOptions();
        
        return response()->json([
            'success' => true,
            'data' => $options,
        ]);
    }
}