<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Specs extends Model
{
    use HasFactory;

    protected $fillable = ['car_term_id','value', 'status'];

    public function carTerm()
    {
        return $this->belongsTo(CarTerm::class, 'car_term_id');
    }
}
