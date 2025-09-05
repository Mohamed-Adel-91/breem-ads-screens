<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    use HasFactory;
    protected $fillable = ['title_ar', 'title_en', 'description_ar', 'description_en', 'file_path', 'file_type', 'duration_seconds', 'status', 'created_by', 'approved_by', 'start_date', 'end_date'];
    public function screens()
    {
        return $this->belongsToMany(Screen::class)->withPivot('play_order')->withTimestamps();
    }
}
