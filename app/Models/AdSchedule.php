<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdSchedule extends Model
{
    use HasFactory;
    protected $fillable = [
        'ad_id',
        'screen_id',
        'start_time',
        'end_time',
        'is_active'
    ];
    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }
    public function screen()
    {
        return $this->belongsTo(Screen::class);
    }
}
