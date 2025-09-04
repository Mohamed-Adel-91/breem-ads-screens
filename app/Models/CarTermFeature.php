<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CarTermFeature extends Pivot
{
    use HasFactory;

    protected $table = 'car_term_feature';
    protected $fillable = [
        'car_term_id',
        'feature_id',
        'priority',
        'value',
        'status',
    ];

    public function carTerm()
    {
        return $this->belongsTo(CarTerm::class, 'car_term_id');
    }
    public function feature()
    {
        return $this->belongsTo(Feature::class, 'feature_id');
    }
}
