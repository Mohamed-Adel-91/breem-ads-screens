<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Feature extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'feature_category_id',
        'name',
        'status',
    ];
    public function category()
    {
        return $this->belongsTo(FeatureCategory::class , 'feature_category_id');
    }
    public function terms()
    {
        return $this->belongsToMany(CarTerm::class, 'car_term_feature');
    }
}
