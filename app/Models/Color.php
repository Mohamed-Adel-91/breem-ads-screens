<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\CarModel;
use App\Models\CarModelColor;

class Color extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'image'];

    public const UPLOAD_FOLDER = 'upload/colors/';

    public function models()
    {
        return $this->belongsToMany(CarModel::class, 'car_model_color')
            ->using(CarModelColor::class)
            ->withPivot('image')
            ->withTimestamps();
    }

    public function getImagePathAttribute()
    {
        return $this->image ? asset(self::UPLOAD_FOLDER . $this->image) : null;
    }
}
