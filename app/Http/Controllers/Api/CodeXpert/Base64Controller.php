<?php

namespace App\Http\Controllers\Api\CodeXpert;

use App\Http\Controllers\Controller;
use App\Http\Requests\CodeXpert\Base64Request;
use App\Http\Resources\CodeXpert\Base64Resource;
use App\Services\CodeXpert\Base64Service;
use Illuminate\Http\JsonResponse;

class Base64Controller extends Controller
{
    public function __construct(
        private Base64Service $base64Service
    ) {}

    public function process(Base64Request $request): Base64Resource|JsonResponse
    {
        try {
            $result = $this->base64Service->process(
                $request->validated('input'),
                $request->validated('operation'),
                $request->validated('input_type'),
                $request->validated('url_safe'),
                $request->validated('include_line_breaks')
            );

            return new Base64Resource($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process Base64: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getOptions(): JsonResponse
    {
        $options = $this->base64Service->getOptions();
        
        return response()->json([
            'success' => true,
            'data' => $options,
        ]);
    }
}