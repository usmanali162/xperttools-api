<?php

namespace App\Http\Controllers\Api\TextXpert;

use App\Http\Controllers\Controller;
use App\Http\Requests\TextXpert\GenerateLoremRequest;
use App\Http\Resources\TextXpert\LoremIpsumResource;
use App\Services\TextXpert\LoremIpsumService;
use Illuminate\Http\JsonResponse;

class LoremIpsumController extends Controller
{
    public function __construct(
        private LoremIpsumService $loremIpsumService
    ) {}

    public function generate(GenerateLoremRequest $request): LoremIpsumResource|JsonResponse
    {
        try {
            $result = $this->loremIpsumService->generateText(
                $request->validated('type'),
                $request->validated('count'),
                $request->validated('start_with_classic')
            );

            return new LoremIpsumResource($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate lorem ipsum: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getOptions(): JsonResponse
    {
        $options = $this->loremIpsumService->getGenerationOptions();
        
        return response()->json([
            'success' => true,
            'data' => $options,
        ]);
    }
}