<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;


class Setting extends Model
{
    use HasFactory, HasTranslations;
    protected $table = 'settings';
    protected $fillable = [
        'email',
        'slogan',
        'address',
        'phone',
        'hotline',
        'location',
        'facebook',
        'youtube',
        'instagram',
        'linkedin',
        'hr_mail',
        'customer_service_mail',
    ];
    public $translatable = ['address', 'slogan'];
    protected $casts = [
        'address' => 'json',
        'slogan' => 'json',
    ];
}
