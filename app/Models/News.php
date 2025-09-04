<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $table = 'news';

    protected $fillable = [
        'writer_id',
        'title',
        'short_desc',
        'details',
        'added_date',
        'scheduale_date',
        'related_tags',
        'home_img',
        'thumb_img',
        'number_of_reads',
        'home',
        'altText',
        'hidden',
        'is_old',
    ];

    protected $casts = [
        'home' => 'boolean',
        'hidden' => 'boolean',
        'is_old' => 'boolean',
        'added_date' => 'datetime',
        'scheduale_date' => 'datetime',
    ];



    public const UPLOAD_FOLDER = 'upload/news/';

    public function writer()
    {
        return $this->belongsTo(Writer::class);
    }

    public function getHomeImgPathAttribute()
    {
        return $this->home_img ? asset(self::UPLOAD_FOLDER . $this->home_img) : null;
    }

    public function getThumbImgPathAttribute()
    {
        return $this->thumb_img ? asset(self::UPLOAD_FOLDER . $this->thumb_img) : null;
    }

    public function getThumbUrlAttribute(): string
    {
        $path = $this->is_old ? $this->thumb_img : $this->thumb_img_path;

        if (!$path) {
            return asset('img/homepage/news.png');
        }

        return $path;
    }
    public function getHomeUrlAttribute(): string
    {
        $path = $this->is_old ? $this->home_img : $this->home_img_path;

        if (!$path) {
            return asset('img/homepage/news.png');
        }

        return $path;
    }

    public function getAddedDateArAttribute(): string
    {
        if (!$this->added_date) {
            return '';
        }

        $dt = $this->added_date instanceof Carbon
            ? $this->added_date
            : Carbon::parse($this->added_date)->timezone('Africa/Cairo');

        $text = $dt->locale('ar')->translatedFormat('l j F Y');

        return preg_replace_callback('/\d/', function ($m) {
            return ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'][$m[0]];
        }, $text);
    }

    public function scopePublished($q)
    {
        return $q->where(function ($q) {
            $q->whereNull('scheduale_date')
                ->orWhere('scheduale_date', '<=', now());
        });
    }
}
