<?php

namespace App\Services\MediaXpert;

use App\Services\BaseToolService;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Exception;

class VideoDownloaderService extends BaseToolService
{
    private array $platforms;
    private array $supportedPlatforms = [
        'youtube' => ['youtube.com', 'youtu.be', 'm.youtube.com'],
        'facebook' => ['facebook.com', 'fb.watch', 'm.facebook.com'],
        'instagram' => ['instagram.com', 'instagr.am'],
        'twitter' => ['twitter.com', 'x.com', 't.co'],
        'tiktok' => ['tiktok.com', 'vm.tiktok.com'],
        'vimeo' => ['vimeo.com'],
        'dailymotion' => ['dailymotion.com', 'dai.ly'],
        'linkedin' => ['linkedin.com'],
    ];

    public function __construct()
    {
        // Only initialize YouTube downloader for now
        // Other platforms will return "not implemented" errors
        $this->platforms = [
            'youtube' => new YouTubeDownloader(),
            // Placeholder - will return not implemented errors
            'facebook' => null,
            'instagram' => null,
            'twitter' => null,
            'tiktok' => null,
            'vimeo' => null,
            'dailymotion' => null,
            'linkedin' => null,
        ];
    }

    public function downloadVideo(
        string $url,
        string $format = 'mp4',
        string $quality = '720p',
        bool $audioOnly = false,
        ?string $platform = null
    ): array {
        $this->validateInput([
            'url' => $url,
            'format' => $format,
            'quality' => $quality,
            'audio_only' => $audioOnly,
            'platform' => $platform,
        ], [
            'url' => 'required|url|max:2000',
            'format' => 'required|string|in:mp4,webm,mkv,mp3,wav,aac',
            'quality' => 'required|string|in:144p,240p,360p,480p,720p,1080p,1440p,2160p,best,worst',
            'audio_only' => 'boolean',
            'platform' => 'nullable|string|in:youtube,facebook,instagram,twitter,tiktok,vimeo,dailymotion,linkedin',
        ]);

        try {
            // Auto-detect platform if not provided
            $detectedPlatform = $platform ?? $this->detectPlatform($url);
            
            // Check if platform is supported
            if (!array_key_exists($detectedPlatform, $this->platforms)) {
                throw new InvalidArgumentException("Unsupported platform: {$detectedPlatform}");
            }
            
            // Check if platform implementation exists
            if ($this->platforms[$detectedPlatform] === null) {
                return [
                    'success' => false,
                    'error' => ucfirst($detectedPlatform) . " downloader is not yet implemented. Currently only YouTube is supported.",
                    'platform' => $detectedPlatform,
                    'supported_platforms' => ['youtube'],
                ];
            }

            // Check rate limits
            $rateLimitResult = $this->checkRateLimit('video_downloader');
            if (!$rateLimitResult) {
                return [
                    'success' => false,
                    'error' => 'Rate limit exceeded. Please try again later or upgrade to premium.',
                    'platform' => $detectedPlatform,
                ];
            }

            // Get video info first
            $videoInfo = $this->getVideoInfo($url, $detectedPlatform);
            
            if (!$videoInfo['success']) {
                return $videoInfo;
            }

            // Download the video
            $downloadResult = $this->platforms[$detectedPlatform]->download(
                $url, 
                $format, 
                $quality, 
                $audioOnly
            );

            $this->logUsage('video_downloader', [
                'platform' => $detectedPlatform,
                'format' => $format,
                'quality' => $quality,
                'audio_only' => $audioOnly,
                'video_duration' => $videoInfo['data']['duration'] ?? null,
            ]);

            return array_merge($downloadResult, [
                'platform' => $detectedPlatform,
                'video_info' => $videoInfo['data'],
                'options_used' => [
                    'format' => $format,
                    'quality' => $quality,
                    'audio_only' => $audioOnly,
                ],
            ]);

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'platform' => $detectedPlatform ?? 'unknown',
            ];
        }
    }

    public function getVideoInfo(string $url, ?string $platform = null): array
    {
        try {
            $detectedPlatform = $platform ?? $this->detectPlatform($url);
            
            if (!array_key_exists($detectedPlatform, $this->platforms)) {
                throw new InvalidArgumentException("Unsupported platform: {$detectedPlatform}");
            }
            
            if ($this->platforms[$detectedPlatform] === null) {
                return [
                    'success' => false,
                    'error' => ucfirst($detectedPlatform) . " info extraction is not yet implemented. Currently only YouTube is supported.",
                ];
            }

            return $this->platforms[$detectedPlatform]->getVideoInfo($url);

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function detectPlatform(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        $host = strtolower(preg_replace('/^www\./', '', $host));

        foreach ($this->supportedPlatforms as $platform => $domains) {
            foreach ($domains as $domain) {
                if (str_contains($host, $domain)) {
                    return $platform;
                }
            }
        }

        throw new InvalidArgumentException(
            "Could not detect platform from URL. Supported platforms: " . 
            implode(', ', array_keys($this->supportedPlatforms))
        );
    }

    public function getSupportedPlatforms(): array
    {
        return array_keys($this->supportedPlatforms);
    }

    public function getSupportedFormats(): array
    {
        return [
            'video' => ['mp4', 'webm', 'mkv'],
            'audio' => ['mp3', 'wav', 'aac'],
        ];
    }

    public function getSupportedQualities(): array
    {
        return [
            '144p' => 'Low (144p)',
            '240p' => 'Low (240p)', 
            '360p' => 'Medium (360p)',
            '480p' => 'Medium (480p)',
            '720p' => 'HD (720p)',
            '1080p' => 'Full HD (1080p)',
            '1440p' => 'QHD (1440p) - Premium',
            '2160p' => '4K (2160p) - Premium',
            'best' => 'Best Available',
            'worst' => 'Smallest Size',
        ];
    }

    public function getOptions(): array
    {
        return [
            'supported_platforms' => $this->getSupportedPlatforms(),
            'supported_formats' => $this->getSupportedFormats(),
            'supported_qualities' => $this->getSupportedQualities(),
            'free_limits' => [
                'daily_downloads' => 5,
                'max_quality' => '720p',
                'max_duration' => '10 minutes',
            ],
            'premium_features' => [
                'unlimited_downloads' => true,
                'up_to_4k' => true,
                'batch_processing' => true,
                'priority_queue' => true,
                'no_watermark' => true,
            ],
            'examples' => [
                'youtube' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'facebook' => 'https://www.facebook.com/video.php?v=1234567890',
                'instagram' => 'https://www.instagram.com/p/ABC123/',
                'twitter' => 'https://twitter.com/user/status/1234567890',
                'tiktok' => 'https://www.tiktok.com/@user/video/1234567890',
            ],
        ];
    }
}

// Platform-specific downloader interfaces
interface PlatformDownloaderInterface
{
    public function download(string $url, string $format, string $quality, bool $audioOnly): array;
    public function getVideoInfo(string $url): array;
    public function validateUrl(string $url): bool;
}

class YouTubeDownloader implements PlatformDownloaderInterface
{
    public function download(string $url, string $format, string $quality, bool $audioOnly): array
    {
        try {
            // Get video info first for title
            $videoInfo = $this->getVideoInfo($url);
            if (!$videoInfo['success']) {
                throw new Exception('Could not get video information');
            }
            
            // Create marketing-friendly filename
            $videoId = $this->extractVideoId($url);
            $videoTitle = $this->sanitizeFilename($videoInfo['data']['title']);
            $extension = $audioOnly ? 'mp3' : $format;
            
            // Format: XpertTools - VideoTitle.ext (clean, professional)
            $filename = "XpertTools - {$videoTitle}.{$extension}";
            $downloadPath = storage_path('app/downloads/' . $filename);
            
            // Build simpler yt-dlp command
            $command = $this->buildSimpleCommand($url, $downloadPath, $format, $quality, $audioOnly);
            
            // Execute command using exec()
            $output = [];
            $returnCode = 0;
            exec($command . ' 2>&1', $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new Exception("yt-dlp failed: " . implode("\n", $output));
            }
            
            // Check if file was created (yt-dlp might change the filename)
            $cleanTitle = $this->sanitizeFilename($videoInfo['data']['title']);
            $expectedPattern = storage_path('app/downloads/XpertTools - ' . $cleanTitle . '.*');
            $files = glob($expectedPattern);
            
            if (empty($files)) {
                // Fallback: look for any recently created file
                $files = glob(storage_path('app/downloads/*.' . $extension));
                $files = array_filter($files, function($file) {
                    return (time() - filemtime($file)) < 300; // Created within last 5 minutes
                });
            }
            
            if (empty($files)) {
                throw new Exception('Download file not found after yt-dlp execution');
            }
            
            $actualFile = $files[0];
            $actualFilename = basename($actualFile);
            
            return [
                'success' => true,
                'download_url' => '/api/v1/mediaxpert/downloads/' . urlencode($actualFilename),
                'filename' => $actualFilename,
                'file_size' => filesize($actualFile),
                'file_path' => $actualFile,
                'branding' => 'Downloaded via XpertTools.com',
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Download failed: ' . $e->getMessage(),
            ];
        }
    }

    private function sanitizeFilename(string $title): string
    {
        // Keep more characters for better readability, just remove filesystem-dangerous ones
        $sanitized = preg_replace('/[<>:"\\/\\\\|?*]/', '', $title);
        $sanitized = preg_replace('/\s+/', ' ', trim($sanitized));
        $sanitized = substr($sanitized, 0, 80); // Limit to 80 characters
        
        return $sanitized ?: 'Video';
    }

    private function buildSimpleCommand(string $url, string $outputPath, string $format, string $quality, bool $audioOnly): string
    {
        $baseCommand = 'yt-dlp --no-playlist --no-warnings';
        
        // Add user agent and cookies to bypass some restrictions
        $baseCommand .= ' --user-agent "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"';
        $baseCommand .= ' --add-header "Accept-Language:en-US,en;q=0.9"';
        
        if ($audioOnly) {
            $baseCommand .= ' --extract-audio --audio-format mp3 --audio-quality 0';
        } else {
            // Try multiple format strategies
            if ($quality === 'best') {
                $baseCommand .= ' --format "best[ext=mp4]/best"';
            } elseif ($quality === 'worst') {
                $baseCommand .= ' --format "worst[ext=mp4]/worst"';
            } else {
                $qualityHeight = $this->parseQuality($quality);
                // Multiple fallback options
                $baseCommand .= " --format \"best[height<={$qualityHeight}][ext=mp4]/best[height<={$qualityHeight}]/best[ext=mp4]/best\"";
            }
        }
        
        $baseCommand .= ' --output ' . escapeshellarg($outputPath);
        $baseCommand .= ' ' . escapeshellarg($url);
        
        return $baseCommand;
    }

    public function getVideoInfo(string $url): array
    {
        try {
            // Use yt-dlp to get video info
            $command = [
                'yt-dlp',
                '--dump-json',
                '--no-playlist',
                escapeshellarg($url)
            ];
            
            $process = proc_open(implode(' ', $command), [
                1 => ['pipe', 'w'], // stdout
                2 => ['pipe', 'w'], // stderr
            ], $pipes);
            
            if (!is_resource($process)) {
                throw new Exception('Failed to start yt-dlp info process');
            }
            
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $returnCode = proc_close($process);
            
            if ($returnCode !== 0) {
                throw new Exception("yt-dlp info failed: " . $stderr);
            }
            
            $info = json_decode($stdout, true);
            
            if (!$info) {
                throw new Exception('Failed to parse video info JSON');
            }
            
            return [
                'success' => true,
                'data' => [
                    'title' => $info['title'] ?? 'Unknown',
                    'duration' => $info['duration'] ?? 0,
                    'thumbnail' => $info['thumbnail'] ?? null,
                    'uploader' => $info['uploader'] ?? 'Unknown',
                    'view_count' => $info['view_count'] ?? 0,
                    'upload_date' => $info['upload_date'] ?? null,
                    'description' => substr($info['description'] ?? '', 0, 200),
                ],
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to get video info: ' . $e->getMessage(),
            ];
        }
    }

    public function validateUrl(string $url): bool
    {
        return preg_match('/^https?:\/\/(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)[\w-]+/', $url);
    }

    private function extractVideoId(string $url): string
    {
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }
        return 'unknown';
    }



    private function parseQuality(string $quality): int
    {
        if ($quality === 'best') return 9999;
        if ($quality === 'worst') return 144;
        return (int) str_replace('p', '', $quality);
    }
}

// Placeholder classes for other platforms - would need full implementation
class FacebookDownloader implements PlatformDownloaderInterface
{
    public function download(string $url, string $format, string $quality, bool $audioOnly): array
    {
        // Facebook-specific implementation
        return ['success' => false, 'error' => 'Facebook downloader not yet implemented'];
    }

    public function getVideoInfo(string $url): array
    {
        return ['success' => false, 'error' => 'Facebook info not yet implemented'];
    }

    public function validateUrl(string $url): bool
    {
        return str_contains($url, 'facebook.com');
    }
}

class InstagramDownloader implements PlatformDownloaderInterface
{
    public function download(string $url, string $format, string $quality, bool $audioOnly): array
    {
        return ['success' => false, 'error' => 'Instagram downloader not yet implemented'];
    }

    public function getVideoInfo(string $url): array
    {
        return ['success' => false, 'error' => 'Instagram info not yet implemented'];
    }

    public function validateUrl(string $url): bool
    {
        return str_contains($url, 'instagram.com');
    }
}

class TwitterDownloader implements PlatformDownloaderInterface
{
    public function download(string $url, string $format, string $quality, bool $audioOnly): array
    {
        return ['success' => false, 'error' => 'Twitter downloader not yet implemented'];
    }

    public function getVideoInfo(string $url): array
    {
        return ['success' => false, 'error' => 'Twitter info not yet implemented'];
    }

    public function validateUrl(string $url): bool
    {
        return str_contains($url, 'twitter.com') || str_contains($url, 'x.com');
    }
}

class TikTokDownloader implements PlatformDownloaderInterface
{
    public function download(string $url, string $format, string $quality, bool $audioOnly): array
    {
        return ['success' => false, 'error' => 'TikTok downloader not yet implemented'];
    }

    public function getVideoInfo(string $url): array
    {
        return ['success' => false, 'error' => 'TikTok info not yet implemented'];
    }

    public function validateUrl(string $url): bool
    {
        return str_contains($url, 'tiktok.com');
    }
}

class VimeoDownloader implements PlatformDownloaderInterface
{
    public function download(string $url, string $format, string $quality, bool $audioOnly): array
    {
        return ['success' => false, 'error' => 'Vimeo downloader not yet implemented'];
    }

    public function getVideoInfo(string $url): array
    {
        return ['success' => false, 'error' => 'Vimeo info not yet implemented'];
    }

    public function validateUrl(string $url): bool
    {
        return str_contains($url, 'vimeo.com');
    }
}

class DailymotionDownloader implements PlatformDownloaderInterface
{
    public function download(string $url, string $format, string $quality, bool $audioOnly): array
    {
        return ['success' => false, 'error' => 'Dailymotion downloader not yet implemented'];
    }

    public function getVideoInfo(string $url): array
    {
        return ['success' => false, 'error' => 'Dailymotion info not yet implemented'];
    }

    public function validateUrl(string $url): bool
    {
        return str_contains($url, 'dailymotion.com');
    }
}

class LinkedInDownloader implements PlatformDownloaderInterface
{
    public function download(string $url, string $format, string $quality, bool $audioOnly): array
    {
        return ['success' => false, 'error' => 'LinkedIn downloader not yet implemented'];
    }

    public function getVideoInfo(string $url): array
    {
        return ['success' => false, 'error' => 'LinkedIn info not yet implemented'];
    }

    public function validateUrl(string $url): bool
    {
        return str_contains($url, 'linkedin.com');
    }
}