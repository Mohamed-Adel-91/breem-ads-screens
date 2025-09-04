<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CarTerm extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'car_model_id',
        'term_name',
        'price',
        'inventory',
        'reservation_amount',
        'status',
    ];

    public function model()
    {
        return $this->belongsTo(CarModel::class, 'car_model_id');
    }

    public function specs()
    {
        return $this->hasMany(Specs::class, 'car_term_id');
    }

    public function features()
    {
        return $this->belongsToMany(Feature::class, 'car_term_feature')
            ->using(CarTermFeature::class)
            ->withPivot('priority', 'value', 'status')
            ->withTimestamps();
    }
}
