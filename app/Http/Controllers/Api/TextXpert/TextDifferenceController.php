<?php

namespace App\Http\Controllers\Api\TextXpert;

use App\Http\Controllers\Controller;
use App\Http\Requests\TextXpert\CompareTextRequest;
use App\Http\Resources\TextXpert\TextDifferenceResource;
use App\Services\TextXpert\TextDifferenceService;
use Illuminate\Http\JsonResponse;

class TextDifferenceController extends Controller
{
    public function __construct(
        private TextDifferenceService $textDifferenceService
    ) {}

    public function compare(CompareTextRequest $request): TextDifferenceResource|JsonResponse
    {
        try {
            $result = $this->textDifferenceService->compareTexts(
                $request->validated('original_text'),
                $request->validated('modified_text'),
                $request->validated('comparison_type')
            );

            return new TextDifferenceResource($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to compare texts: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getOptions(): JsonResponse
    {
        $options = $this->textDifferenceService->getComparisonOptions();
        
        return response()->json([
            'success' => true,
            'data' => $options,
        ]);
    }
}