<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class PageSection extends Model
{

    use HasTranslations;
    protected $fillable = ['page_id', 'type', 'order', 'is_active', 'settings'];
    public $translatable = ['settings'];
    protected $casts = ['settings' => 'array'];
    public function items()
    {
        return $this->hasMany(SectionItem::class, 'section_id');
    }
    public function page()
    {
        return $this->belongsTo(Page::class);
    }
}
