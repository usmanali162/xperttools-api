<?php

namespace App\Models\UtiliXpert;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UrlClick extends Model
{
    use HasUuids;
    
    public $timestamps = false;

    protected $fillable = [
        'shortened_url_id',
        'ip_address',
        'user_agent',
        'referer',
        'country',
        'city',
        'clicked_at',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
    ];

    public function shortenedUrl(): BelongsTo
    {
        return $this->belongsTo(ShortenedUrl::class);
    }
}