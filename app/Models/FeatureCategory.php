<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class FeatureCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name'];
    public function features()
    {
        return $this->hasMany(Feature::class);
    }
}
