<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarInfoFeature extends Model
{
    use HasFactory;

    protected $table = 'car_info_features';

    protected $fillable = [
        'car_model_id',
        'image',
        'title',
        'description',
        'rank',
    ];

    public const UPLOAD_FOLDER = 'upload/car_info_features/';

    public function carModel()
    {
        return $this->belongsTo(CarModel::class);
    }

    public function getImagePathAttribute()
    {
        return $this->image ? asset(self::UPLOAD_FOLDER . $this->image) : null;
    }
}
