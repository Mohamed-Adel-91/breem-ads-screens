<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class SeoMeta extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'page',
        'title',
        'description',
        'keywords',
        'og_title',
        'og_description',
        'canonical',
    ];

    public $translatable = ['title', 'description', 'keywords', 'og_title', 'og_description'];
}
