<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'name',
        'logo',
        'banner',
        'banner_tablet',
        'banner_mobile',
        'slider_images',
        'show_status',
        'description',
    ];
    protected $casts = [
        'slider_images' => 'array',
    ];
    public const UPLOAD_FOLDER = 'upload/brands/';
    public function cars()
    {
        return $this->hasMany(CarModel::class);
    }
    public function serviceCenters()
    {
        return $this->hasMany(ServiceCenter::class);
    }

    public function getLogoPathAttribute()
    {
        return $this->logo ? asset(self::UPLOAD_FOLDER . $this->logo) : null;
    }

    public function getBannerPathAttribute()
    {
        return $this->banner ? asset(self::UPLOAD_FOLDER . $this->banner) : null;
    }

    public function getBannerTabletPathAttribute()
    {
        return $this->banner_tablet ? asset(self::UPLOAD_FOLDER . $this->banner_tablet) : null;
    }
    public function getBannerMobilePathAttribute()
    {
        return $this->banner_mobile ? asset(self::UPLOAD_FOLDER . $this->banner_mobile) : null;
    }
    public function getSliderImagesPathsAttribute()
    {
        if ($this->slider_images) {
            return array_map(fn($image) => asset(self::UPLOAD_FOLDER . $image), $this->slider_images);
        }
        return [];
    }
}
