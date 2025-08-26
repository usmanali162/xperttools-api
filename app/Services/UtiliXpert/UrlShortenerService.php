<?php

namespace App\Services\UtiliXpert;

use App\Models\UtiliXpert\ShortenedUrl;
use App\Models\UtiliXpert\UrlClick;
use App\Services\BaseToolService;
use Illuminate\Support\Str;

class UrlShortenerService extends BaseToolService
{
    public function shortenUrl(
        string $url,
        ?string $customCode = null,
        ?int $expirationDays = null,
        ?string $ipAddress = null
    ): array {
        $this->validateInput(['url' => $url], [
            'url' => 'required|url|max:2000',
        ]);

        if ($customCode) {
            $this->validateInput(['custom_code' => $customCode], [
                'custom_code' => 'required|string|max:50|regex:/^[a-zA-Z0-9\-_]+$/',
            ]);
            
            // Check if custom code already exists
            if (ShortenedUrl::where('short_code', $customCode)->exists()) {
                throw new \InvalidArgumentException("Custom code '{$customCode}' is already taken.");
            }
        }

        // Check if URL already exists (within last 30 days)
        $existing = ShortenedUrl::where('original_url', $url)
            ->where('created_at', '>=', now()->subDays(30))
            ->first();

        if ($existing && !$existing->isExpired()) {
            return $this->formatResponse($existing);
        }

        // Use custom code or generate random one
        if ($customCode) {
            $shortCode = $customCode;
        } else {
            // Generate unique random code
            do {
                $shortCode = $this->generateShortCode();
            } while (ShortenedUrl::where('short_code', $shortCode)->exists());
        }

        // Get page title (basic implementation)
        $title = $this->fetchPageTitle($url);

        // Create shortened URL
        $shortenedUrl = ShortenedUrl::create([
            'short_code' => $shortCode,
            'original_url' => $url,
            'title' => $title,
            'expires_at' => $expirationDays ? now()->addDays($expirationDays) : null,
            'ip_address' => $ipAddress,
        ]);

        $this->logUsage('url_shortener', [
            'url_length' => strlen($url),
            'custom_code' => $customCode ? true : false,
            'expiration_days' => $expirationDays,
        ]);

        return $this->formatResponse($shortenedUrl);
    }

    public function getUrlAnalytics(string $shortCode): array
    {
        $shortenedUrl = ShortenedUrl::with('clicks')
            ->where('short_code', $shortCode)
            ->firstOrFail();

        $clicks = $shortenedUrl->clicks()
            ->orderBy('clicked_at', 'desc')
            ->get();

        $analytics = [
            'basic_info' => [
                'short_code' => $shortenedUrl->short_code,
                'short_url' => $shortenedUrl->short_url,
                'original_url' => $shortenedUrl->original_url,
                'title' => $shortenedUrl->title,
                'created_at' => $shortenedUrl->created_at,
                'expires_at' => $shortenedUrl->expires_at,
                'total_clicks' => $shortenedUrl->click_count,
            ],
            'click_analytics' => [
                'today' => $clicks->where('clicked_at', '>=', now()->startOfDay())->count(),
                'this_week' => $clicks->where('clicked_at', '>=', now()->startOfWeek())->count(),
                'this_month' => $clicks->where('clicked_at', '>=', now()->startOfMonth())->count(),
                'last_30_days' => $clicks->where('clicked_at', '>=', now()->subDays(30))->count(),
            ],
            'referrer_analytics' => $this->analyzeReferrers($clicks),
            'geographic_analytics' => $this->analyzeGeography($clicks),
            'recent_clicks' => $clicks->take(10)->map(function ($click) {
                return [
                    'clicked_at' => $click->clicked_at,
                    'ip_address' => substr($click->ip_address, 0, -2) . 'xx', // Mask IP for privacy
                    'referer' => $click->referer,
                    'country' => $click->country,
                    'city' => $click->city,
                ];
            }),
        ];

        return $analytics;
    }

    public function redirectToUrl(string $shortCode, array $clickData = []): string
    {
        $shortenedUrl = ShortenedUrl::where('short_code', $shortCode)->firstOrFail();

        if ($shortenedUrl->isExpired()) {
            throw new \Exception('This short URL has expired.');
        }

        // Record click
        $this->recordClick($shortenedUrl, $clickData);

        return $shortenedUrl->original_url;
    }

    private function generateShortCode(): string
    {
        return Str::random(6);
    }

    private function fetchPageTitle(string $url): ?string
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'user_agent' => 'XpertTools URL Shortener 1.0',
                ]
            ]);
            
            $html = @file_get_contents($url, false, $context);
            if (!$html) return null;

            if (preg_match('/<title[^>]*>(.*?)<\/title>/i', $html, $matches)) {
                return trim(html_entity_decode($matches[1]));
            }
        } catch (\Exception $e) {
            // Ignore errors
        }
        
        return null;
    }

    private function recordClick(ShortenedUrl $shortenedUrl, array $clickData): void
    {
        UrlClick::create([
            'shortened_url_id' => $shortenedUrl->id,
            'ip_address' => $clickData['ip_address'] ?? '',
            'user_agent' => $clickData['user_agent'] ?? '',
            'referer' => $clickData['referer'] ?? '',
            'country' => $clickData['country'] ?? null,
            'city' => $clickData['city'] ?? null,
            'clicked_at' => now(),
        ]);

        $shortenedUrl->incrementClicks();
    }

    private function analyzeReferrers($clicks): array
    {
        return $clicks->groupBy('referer')
            ->map(function ($group, $referer) {
                return [
                    'referer' => $referer ?: 'Direct',
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('count')
            ->take(10)
            ->values()
            ->toArray();
    }

    private function analyzeGeography($clicks): array
    {
        return $clicks->whereNotNull('country')
            ->groupBy('country')
            ->map(function ($group, $country) {
                return [
                    'country' => $country,
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('count')
            ->take(10)
            ->values()
            ->toArray();
    }

    private function formatResponse(ShortenedUrl $shortenedUrl): array
    {
        return [
            'short_code' => $shortenedUrl->short_code,
            'short_url' => $shortenedUrl->short_url,
            'original_url' => $shortenedUrl->original_url,
            'title' => $shortenedUrl->title,
            'click_count' => $shortenedUrl->click_count,
            'created_at' => $shortenedUrl->created_at,
            'expires_at' => $shortenedUrl->expires_at,
        ];
    }
}