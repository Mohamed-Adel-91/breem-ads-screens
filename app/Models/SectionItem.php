<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class SectionItem extends Model
{
    use HasTranslations;
    protected $fillable = ['section_id', 'order', 'is_active', 'data', 'media_id'];
    public $translatable = ['data'];
    protected $casts = ['data' => 'array', 'is_active' => 'boolean'];
    protected $attributes = ['is_active' => true];
    public function section()
    {
        return $this->belongsTo(PageSection::class);
    }

    /**
     * Only the items the public website should render.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
