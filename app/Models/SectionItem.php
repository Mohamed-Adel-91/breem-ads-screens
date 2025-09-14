<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class SectionItem extends Model
{
    use HasTranslations;
    protected $fillable = ['section_id', 'order', 'data', 'media_id'];
    public $translatable = ['data'];
    protected $casts = ['data' => 'array'];
    public function section()
    {
        return $this->belongsTo(PageSection::class);
    }
}
