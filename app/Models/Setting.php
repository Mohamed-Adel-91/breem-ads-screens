<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Setting extends Model
{
    use HasFactory, HasTranslations;
    protected $fillable = ['key', 'value'];
    protected $casts = ['value' => 'array'];
    public $translatable = ['value'];
    public function scopeKey($query, $key)
    {
        return $query->where('key', $key);
    }
}
