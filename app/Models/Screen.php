<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Screen extends Model
{
    use HasFactory;
    protected $fillable = [
        'place_id',
        'code',
        'device_uid',
        'status',
        'last_heartbeat'
    ];
    public function place()
    {
        return $this->belongsTo(Place::class);
    }
    public function ads()
    {
        return $this->belongsToMany(Ad::class)->withPivot('play_order')->withTimestamps();
    }
    public function schedules()
    {
        return $this->hasMany(AdSchedule::class);
    }
}
