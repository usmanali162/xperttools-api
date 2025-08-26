<?php

namespace App\Models\UtiliXpert;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ShortenedUrl extends Model
{
    use HasUuids;

    protected $fillable = [
        'short_code',
        'original_url',
        'title',
        'description',
        'click_count',
        'click_data',
        'expires_at',
        'user_id',
        'ip_address',
    ];

    protected $casts = [
        'click_data' => 'array',
        'expires_at' => 'datetime',
    ];

    protected $attributes = [
        'click_count' => 0,
    ];

    public function clicks(): HasMany
    {
        return $this->hasMany(UrlClick::class);
    }

    public function getShortUrlAttribute(): string
    {
        return config('app.url') . '/s/' . $this->short_code;
    }

    public function incrementClicks(): void
    {
        $this->increment('click_count');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}