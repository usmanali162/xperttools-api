<?php

namespace App\Http\Controllers\Api\TextXpert;

use App\Http\Controllers\Controller;
use App\Http\Requests\TextXpert\AnalyzeTextRequest;
use App\Http\Resources\TextXpert\TextAnalysisResource;
use App\Services\TextXpert\TextAnalyzerService;
use Illuminate\Http\JsonResponse;

class TextAnalyzerController extends Controller
{
    public function __construct(
        private TextAnalyzerService $textAnalyzerService
    ) {}

    public function analyze(AnalyzeTextRequest $request): TextAnalysisResource|JsonResponse
    {
        try {
            $result = $this->textAnalyzerService->analyzeText(
                $request->validated('text')
            );

            return new TextAnalysisResource($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to analyze text: ' . $e->getMessage(),
            ], 500);
        }
    }
}