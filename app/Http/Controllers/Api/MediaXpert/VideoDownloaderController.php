<?php

namespace App\Http\Controllers\Api\MediaXpert;

use App\Http\Controllers\Controller;
use App\Http\Requests\MediaXpert\VideoDownloaderRequest;
use App\Http\Resources\MediaXpert\VideoDownloaderResource;
use App\Services\MediaXpert\VideoDownloaderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoDownloaderController extends Controller
{
    public function __construct(
        private VideoDownloaderService $videoDownloaderService
    ) {}

    /**
     * Download video from supported platforms
     */
    public function download(VideoDownloaderRequest $request): JsonResponse
    {
        $startTime = microtime(true);

        $result = $this->videoDownloaderService->downloadVideo(
            url: $request->validated('url'),
            format: $request->validated('format', 'mp4'),
            quality: $request->validated('quality', '720p'),
            audioOnly: $request->validated('audio_only', false),
            platform: $request->validated('platform')
        );

        $result['processing_time'] = round((microtime(true) - $startTime) * 1000, 2) . 'ms';

        return response()->json(
            new VideoDownloaderResource($result),
            $result['success'] ? 200 : 400
        );
    }

    /**
     * Get video information without downloading
     */
    public function getVideoInfo(Request $request): JsonResponse
    {
        $request->validate([
            'url' => [
                'required',
                'url',
                'max:2000',
                'regex:/^https?:\/\/(www\.)?(youtube\.com|youtu\.be|facebook\.com|fb\.watch|instagram\.com|instagr\.am|twitter\.com|x\.com|t\.co|tiktok\.com|vm\.tiktok\.com|vimeo\.com|dailymotion\.com|dai\.ly|linkedin\.com)/',
            ],
            'platform' => 'nullable|string|in:youtube,facebook,instagram,twitter,tiktok,vimeo,dailymotion,linkedin',
        ]);

        $startTime = microtime(true);

        $result = $this->videoDownloaderService->getVideoInfo(
            url: $request->input('url'),
            platform: $request->input('platform')
        );

        $result['processing_time'] = round((microtime(true) - $startTime) * 1000, 2) . 'ms';

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get supported platforms, formats, and options
     */
    public function getOptions(): JsonResponse
    {
        $options = $this->videoDownloaderService->getOptions();

        return response()->json([
            'success' => true,
            'data' => $options,
            'usage_info' => [
                'free_tier' => [
                    'daily_limit' => 5,
                    'max_quality' => '720p',
                    'max_duration' => '10 minutes',
                    'supported_formats' => ['mp4', 'mp3'],
                ],
                'premium_tier' => [
                    'unlimited_downloads' => true,
                    'all_qualities' => 'Up to 4K (2160p)',
                    'all_formats' => ['mp4', 'webm', 'mkv', 'mp3', 'wav', 'aac'],
                    'batch_processing' => true,
                    'priority_queue' => true,
                ],
            ],
            'legal_notice' => [
                'responsibility' => 'Users are responsible for ensuring they have the right to download content.',
                'fair_use' => 'Only download content you own or have permission to download.',
                'copyright' => 'Respect copyright laws and platform terms of service.',
            ],
        ]);
    }

    /**
     * Get platform detection information
     */
    public function detectPlatform(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|url|max:2000',
        ]);

        try {
            $url = $request->input('url');
            $host = parse_url($url, PHP_URL_HOST);
            $host = strtolower(preg_replace('/^www\./', '', $host));

            $supportedPlatforms = [
                'youtube.com' => 'youtube',
                'youtu.be' => 'youtube',
                'facebook.com' => 'facebook',
                'fb.watch' => 'facebook',
                'instagram.com' => 'instagram',
                'instagr.am' => 'instagram',
                'twitter.com' => 'twitter',
                'x.com' => 'twitter',
                'tiktok.com' => 'tiktok',
                'vimeo.com' => 'vimeo',
                'dailymotion.com' => 'dailymotion',
                'linkedin.com' => 'linkedin',
            ];

            $detectedPlatform = null;
            foreach ($supportedPlatforms as $domain => $platform) {
                if (str_contains($host, $domain)) {
                    $detectedPlatform = $platform;
                    break;
                }
            }

            if ($detectedPlatform) {
                return response()->json([
                    'success' => true,
                    'platform' => $detectedPlatform,
                    'supported' => true,
                    'platform_name' => ucfirst($detectedPlatform),
                ]);
            }

            return response()->json([
                'success' => false,
                'platform' => null,
                'supported' => false,
                'error' => 'Platform not supported',
                'supported_platforms' => array_values(array_unique($supportedPlatforms)),
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid URL format',
            ], 400);
        }
    }

    /**
     * Get download history for authenticated users
     */
    public function getHistory(Request $request): JsonResponse
    {
        // TODO: Implement when user authentication is ready
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Download history will be available after user authentication is implemented.',
        ]);
    }

    /**
     * Get usage statistics for rate limiting
     */
    public function getUsageStats(Request $request): JsonResponse
    {
        // TODO: Implement proper rate limiting when user system is ready
        return response()->json([
            'success' => true,
            'usage' => [
                'downloads_today' => 0,
                'downloads_remaining' => 5,
                'reset_time' => now()->endOfDay()->toISOString(),
                'is_premium' => false,
            ],
        ]);
    }
}