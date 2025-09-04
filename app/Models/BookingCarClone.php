<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingCarClone extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'car_brand_id',
        'car_brand_name',
        'car_model_id',
        'car_model_name',
        'car_term_id',
        'car_term_name',
        'color_id',
        'color_name',
        'price',
        'reservation_amount',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
