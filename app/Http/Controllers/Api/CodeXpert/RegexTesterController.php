<?php

namespace App\Http\Controllers\Api\CodeXpert;

use App\Http\Controllers\Controller;
use App\Http\Requests\CodeXpert\TestRegexRequest;
use App\Http\Resources\CodeXpert\RegexTesterResource;
use App\Services\CodeXpert\RegexTesterService;
use Illuminate\Http\JsonResponse;

class RegexTesterController extends Controller
{
    public function __construct(
        private RegexTesterService $regexTesterService
    ) {}

    public function test(TestRegexRequest $request): RegexTesterResource|JsonResponse
    {
        try {
            $operation = $request->validated('operation');
            
            if ($operation === 'replace') {
                $result = $this->regexTesterService->testReplacement(
                    $request->validated('pattern'),
                    $request->validated('test_string'),
                    $request->validated('replacement'),
                    $request->validated('flags'),
                    $request->validated('global_replace')
                );
            } else {
                $result = $this->regexTesterService->testPattern(
                    $request->validated('pattern'),
                    $request->validated('test_string'),
                    $request->validated('flags'),
                    $request->validated('global_match'),
                    $request->validated('explain_pattern')
                );
            }

            return new RegexTesterResource($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to test regex: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getOptions(): JsonResponse
    {
        $options = $this->regexTesterService->getOptions();
        
        return response()->json([
            'success' => true,
            'data' => $options,
        ]);
    }
}