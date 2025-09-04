<?php

namespace App\Models;

use App\Enums\EngineTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CarModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'brand_id',
        'shape_id',
        'name',
        'description',
        'image',
        'banner',
        'start_price',
        'show_status',
        'catalog',
        'year',
        'maintenance_schedule_pdf',
        'view_360_degree',
        'engine',
        'engine_type',
        'horse_power',
        'torque',
        'gear_box',
        'banner_tablet',
        'banner_mobile',
        'is_home',
    ];

    protected $casts = [
        'engine_type' => EngineTypeEnum::class,
    ];

    public const UPLOAD_FOLDER = 'upload/car_models/';

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    public function shape()
    {
        return $this->belongsTo(Shape::class);
    }
    public function terms()
    {
        return $this->hasMany(CarTerm::class);
    }

    public function knowMore()
    {
        return $this->hasMany(KnowMore::class);
    }

    public function knowMoreVideos()
    {
        return $this->hasMany(KnowMore::class)
            ->where('video', '!=', null);
    }

    public function knowMoreHeroVideo()
    {
        return $this->hasOne(KnowMore::class)
            ->where('video', '!=', null)
            ->where('hero_section', true)
            ->latest();
    }

    public function knowMoreImages()
    {
        return $this->hasMany(KnowMore::class)
            ->where('image', '!=', null);
    }

    public function knowMoreHeroImage()
    {
        return $this->hasOne(KnowMore::class)
            ->where('image', '!=', null)
            ->where('hero_section', true)
            ->latest();
    }
    public function exteriors()
    {
        return $this->hasMany(CarExterior::class);
    }

    public function heroExterior()
    {
        return $this->hasOne(CarExterior::class)
            ->where('hero_section', true)
            ->latest();
    }
    public function interiors()
    {
        return $this->hasMany(CarInterior::class);
    }
    public function heroInterior()
    {
        return $this->hasOne(CarInterior::class)
            ->where('hero_section', true)
            ->latest();
    }

    public function infoFeatures()
    {
        return $this->hasMany(CarInfoFeature::class);
    }

    public function colors()
    {
        return $this->belongsToMany(Color::class, 'car_model_color')
            ->using(CarModelColor::class)
            ->withPivot('image')
            ->withTimestamps();
    }

    public function getImagePathAttribute()
    {
        return $this->image ? asset(self::UPLOAD_FOLDER . $this->image) : null;
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

    public function getCatalogPathAttribute()
    {
        return $this->catalog ? asset(self::UPLOAD_FOLDER . $this->catalog) : null;
    }

    public function getMaintenanceSchedulePdfPathAttribute()
    {
        return $this->maintenance_schedule_pdf ? asset(self::UPLOAD_FOLDER . $this->maintenance_schedule_pdf) : null;
    }
}
