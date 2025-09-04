<?php

namespace App\Models;

use App\Enums\CityEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceCenter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'brand_id',
        'name',
        'location',
        'address',
        'phone',
        'city',
    ];

    protected $casts = [
        'city' => CityEnum::class,
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
}
