<?php

namespace App\Http\Controllers\Api\UtiliXpert;

use App\Http\Controllers\Controller;
use App\Http\Requests\UtiliXpert\ShortenUrlRequest;
use App\Services\UtiliXpert\UrlShortenerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UrlShortenerController extends Controller
{
    public function __construct(
        private UrlShortenerService $urlShortenerService
    ) {}

    public function shorten(ShortenUrlRequest $request): JsonResponse
    {
        try {
            $result = $this->urlShortenerService->shortenUrl(
                $request->validated('url'),
                $request->validated('custom_code'),
                $request->validated('expiration_days'),
                $request->ip()
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to shorten URL: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function analytics(string $shortCode): JsonResponse
    {
        try {
            $analytics = $this->urlShortenerService->getUrlAnalytics($shortCode);

            return response()->json([
                'success' => true,
                'data' => $analytics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Analytics not found: ' . $e->getMessage(),
            ], 404);
        }
    }

    public function redirect(string $shortCode, Request $request)
    {
        try {
            $originalUrl = $this->urlShortenerService->redirectToUrl($shortCode, [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer'),
            ]);

            return redirect($originalUrl);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }
}