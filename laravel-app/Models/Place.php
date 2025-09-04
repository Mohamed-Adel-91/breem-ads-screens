<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    use HasFactory;
    protected $fillable = ['name_ar', 'name_en', 'address_ar', 'address_en', 'type'];
    public function screens()
    {
        return $this->hasMany(Screen::class);
    }
}
