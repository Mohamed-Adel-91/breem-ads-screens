<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{

    protected $fillable = ['location', 'is_active'];
    public function items()
    {
        return $this->hasMany(MenuItem::class)->orderBy('order');
    }
}
