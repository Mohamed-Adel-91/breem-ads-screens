<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CarInterior extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'car_interior';
    protected $fillable = [
        'car_model_id',
        'image',
        'hero_section',
    ];

    protected $casts = [
        'hero_section' => 'boolean',
    ];

    public const UPLOAD_FOLDER = 'upload/car_interiors/';

    public function carModel()
    {
        return $this->belongsTo(CarModel::class, 'car_model_id');
    }

    public function getImagePathAttribute()
    {
        return $this->image ? asset(self::UPLOAD_FOLDER . $this->image) : null;
    }

    public function getHeroImagePathAttribute()
    {
        return ($this->hero_section && $this->image)
            ? asset(self::UPLOAD_FOLDER . $this->image)
            : null;
    }
}
