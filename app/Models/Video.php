<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'short_desc',
        'added_date',
        'link',
        'thumb_image',
        'home',
        'hidden',
        'ord',
    ];

    protected $casts = [
        'home' => 'boolean',
        'hidden' => 'boolean',
        'added_date' => 'date',
    ];

    public function getYoutubeIdAttribute(): ?string
    {
        $url = trim((string) $this->link);

        // youtu.be/<id>
        if (preg_match('~youtu\.be/([A-Za-z0-9_-]{11})~i', $url, $m)) return $m[1];

        // youtube.com/watch?v=<id>
        if (preg_match('~v=([A-Za-z0-9_-]{11})~i', $url, $m)) return $m[1];

        // /embed/<id>
        if (preg_match('~/embed/([A-Za-z0-9_-]{11})~i', $url, $m)) return $m[1];

        // /shorts/<id>
        if (preg_match('~/shorts/([A-Za-z0-9_-]{11})~i', $url, $m)) return $m[1];

        return null;
    }

    public function getThumbnailUrlAttribute(): string
    {
        // Start with a conservative size that exists most often
        return $this->youtube_id
            ? "https://img.youtube.com/vi/{$this->youtube_id}/hqdefault.jpg"
            : asset('img/homepage/iop.png');
    }

    public const UPLOAD_FOLDER = 'upload/videos/';

    public function getThumbImagePathAttribute()
    {
        return $this->thumb_image ? asset(self::UPLOAD_FOLDER . $this->thumb_image) : null;
    }
}
