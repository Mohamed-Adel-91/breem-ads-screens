<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CarModelColor extends Pivot
{
    use HasFactory;

    protected $table = 'car_model_color';
    protected $fillable = ['car_model_id', 'color_id', 'image'];

    public const UPLOAD_FOLDER = 'upload/car_models/colors/';
    public function carModel()
    {
        return $this->belongsTo(CarModel::class, 'car_model_id');
    }
    public function color()
    {
        return $this->belongsTo(Color::class, 'color_id');
    }

    public function getImagePathAttribute()
    {
        return $this->image ? asset(self::UPLOAD_FOLDER . $this->image) : null;
    }
}
