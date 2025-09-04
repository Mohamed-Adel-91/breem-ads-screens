<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shape extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['name', 'logo'];

    public const UPLOAD_FOLDER = 'upload/shapes/';
    public function models()
    {
        return $this->hasMany(CarModel::class);
    }
    public function getLogoPathAttribute()
    {
        return $this->logo ? asset(self::UPLOAD_FOLDER . $this->logo) : null;
    }
}
