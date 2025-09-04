<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KnowMore extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'know_more';
    protected $fillable = [
        'car_model_id',
        'title',
        'description',
        'video',
        'image',
        'hero_section',
    ];

    protected $casts = [
        'hero_section' => 'boolean',
    ];

    public const UPLOAD_FOLDER = 'upload/know_more/';

    public function carModel()
    {
        return $this->belongsTo(CarModel::class);
    }

    public function getVideoPathAttribute()
    {
        return $this->video ? asset(self::UPLOAD_FOLDER . $this->video) : null;
    }

    public function getImagePathAttribute()
    {
        return $this->image ? asset(self::UPLOAD_FOLDER . $this->image) : null;
    }

    public function getHeroVideoPathAttribute()
    {
        return ($this->hero_section && $this->video)
            ? asset(self::UPLOAD_FOLDER . $this->video)
            : null;
    }

    public function getHeroImagePathAttribute()
    {
        return ($this->hero_section && $this->image)
            ? asset(self::UPLOAD_FOLDER . $this->image)
            : null;
    }
}
