<?php

namespace App\Http\Controllers\Api\CodeXpert;

use App\Http\Controllers\Controller;
use App\Http\Requests\CodeXpert\BeautifyXmlRequest;
use App\Http\Resources\CodeXpert\XmlBeautifierResource;
use App\Services\CodeXpert\XmlBeautifierService;
use Illuminate\Http\JsonResponse;

class XmlBeautifierController extends Controller
{
    public function __construct(
        private XmlBeautifierService $xmlBeautifierService
    ) {}

    public function beautify(BeautifyXmlRequest $request): XmlBeautifierResource|JsonResponse
    {
        try {
            $result = $this->xmlBeautifierService->beautifyXml(
                $request->validated('input'),
                $request->validated('indent_size'),
                $request->validated('validate_only'),
                $request->validated('remove_comments'),
                $request->validated('sort_attributes')
            );

            return new XmlBeautifierResource($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process XML: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getOptions(): JsonResponse
    {
        $options = $this->xmlBeautifierService->getOptions();
        
        return response()->json([
            'success' => true,
            'data' => $options,
        ]);
    }
}