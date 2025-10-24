<?php

namespace App\Http\Requests\MediaXpert;

use Illuminate\Foundation\Http\FormRequest;

class VideoDownloaderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => [
                'required',
                'url',
                'max:2000',
                'regex:/^https?:\/\/(www\.)?(youtube\.com|youtu\.be|facebook\.com|fb\.watch|instagram\.com|instagr\.am|twitter\.com|x\.com|t\.co|tiktok\.com|vm\.tiktok\.com|vimeo\.com|dailymotion\.com|dai\.ly|linkedin\.com)/',
            ],
            'format' => 'required|string|in:mp4,webm,mkv,mp3,wav,aac',
            'quality' => 'required|string|in:144p,240p,360p,480p,720p,1080p,1440p,2160p,best,worst',
            'audio_only' => 'boolean',
            'platform' => 'nullable|string|in:youtube,facebook,instagram,twitter,tiktok,vimeo,dailymotion,linkedin',
        ];
    }

    public function messages(): array
    {
        return [
            'url.required' => 'Video URL is required.',
            'url.url' => 'Please provide a valid URL.',
            'url.regex' => 'This platform is not supported. We support YouTube, Facebook, Instagram, Twitter, TikTok, Vimeo, Dailymotion, and LinkedIn.',
            'format.in' => 'Invalid format. Supported formats: mp4, webm, mkv, mp3, wav, aac.',
            'quality.in' => 'Invalid quality. Supported qualities: 144p to 2160p.',
        ];
    }

    public function attributes(): array
    {
        return [
            'url' => 'video URL',
            'format' => 'output format',
            'quality' => 'video quality',
            'audio_only' => 'audio only option',
            'platform' => 'platform',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Auto-detect audio-only based on format
        if (in_array($this->format, ['mp3', 'wav', 'aac'])) {
            $this->merge(['audio_only' => true]);
        }

        // Clean and normalize URL
        if ($this->url) {
            $url = trim($this->url);
            
            // Handle mobile URLs
            $url = str_replace(['m.youtube.com', 'm.facebook.com'], ['youtube.com', 'facebook.com'], $url);
            
            // Remove tracking parameters
            $url = preg_replace('/&(utm_|fb_|ig_)[^&]*/', '', $url);
            
            $this->merge(['url' => $url]);
        }
    }
}