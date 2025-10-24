<?php

namespace App\Http\Resources\MediaXpert;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoDownloaderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Simple, safe resource that doesn't access potentially undefined keys
        $data = $this->resource ?? [];
        
        $response = [
            'success' => $data['success'] ?? false,
            'platform' => $data['platform'] ?? null,
        ];

        // Only add sections if they exist and success is true
        if (($data['success'] ?? false) === true) {
            if (isset($data['download_url'])) {
                $response['download'] = [
                    'download_url' => $data['download_url'],
                    'filename' => $data['filename'] ?? null,
                    'expires_at' => now()->addHours(24)->toISOString(),
                ];
            }

            if (isset($data['video_info'])) {
                $response['video_info'] = [
                    'title' => $data['video_info']['title'] ?? null,
                    'duration' => $data['video_info']['duration'] ?? null,
                    'duration_formatted' => $this->formatDuration($data['video_info']['duration'] ?? null),
                    'thumbnail' => $data['video_info']['thumbnail'] ?? null,
                    'uploader' => $data['video_info']['uploader'] ?? null,
                    'view_count' => $data['video_info']['view_count'] ?? null,
                    'view_count_formatted' => $this->formatNumber($data['video_info']['view_count'] ?? null),
                    'upload_date' => $data['video_info']['upload_date'] ?? null,
                ];
            }

            if (isset($data['options_used'])) {
                $response['options_used'] = $data['options_used'];
            }
        } else {
            // Error case
            $response['error'] = [
                'message' => $data['error'] ?? 'An unknown error occurred',
                'code' => $data['error_code'] ?? 'UNKNOWN_ERROR',
            ];
        }

        // Always include processing time if available
        if (isset($data['processing_time'])) {
            $response['processing_time'] = $data['processing_time'];
        }

        // Always include premium features info
        $response['premium_features'] = [
            'available' => [
                'unlimited_downloads' => 'Remove daily download limits',
                'up_to_4k_quality' => 'Download in 1440p and 4K resolution',
                'batch_processing' => 'Download multiple videos at once',
                'priority_queue' => 'Skip waiting times',
                'no_watermark' => 'Clean downloads without watermarks',
            ],
            'upgrade_url' => '/premium/plans',
        ];

        return $response;
    }

    private function formatDuration(?int $seconds): ?string
    {
        if (!$seconds) return null;

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    private function formatNumber(?int $number): ?string
    {
        if (!$number) return null;

        if ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        }

        if ($number >= 1000) {
            return round($number / 1000, 1) . 'K';
        }

        return (string) $number;
    }
}