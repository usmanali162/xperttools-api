<?php

namespace App\Http\Controllers\Api\TextXpert;

use App\Http\Controllers\Controller;
use App\Http\Requests\TextXpert\RemoveDuplicatesRequest;
use App\Http\Resources\TextXpert\DuplicateRemovalResource;
use App\Services\TextXpert\DuplicateRemoverService;
use Illuminate\Http\JsonResponse;

class DuplicateRemoverController extends Controller
{
    public function __construct(
        private DuplicateRemoverService $duplicateRemoverService
    ) {}

    public function remove(RemoveDuplicatesRequest $request): DuplicateRemovalResource|JsonResponse
    {
        try {
            $result = $this->duplicateRemoverService->removeDuplicates(
                $request->validated('text'),
                $request->validated('case_sensitive'),
                $request->validated('trim_whitespace'),
                $request->validated('ignore_empty_lines'),
                $request->validated('sort_order')
            );

            return new DuplicateRemovalResource($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove duplicates: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getOptions(): JsonResponse
    {
        $options = $this->duplicateRemoverService->getRemovalOptions();
        
        return response()->json([
            'success' => true,
            'data' => $options,
        ]);
    }
}