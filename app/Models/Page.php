<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = ['slug', 'name', 'is_active'];
    public function sections()
    {
        return $this->hasMany(PageSection::class)->orderBy('order');
    }
    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}
