<?php

namespace App\Models;

use App\Enums\ScreenStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Screen extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => ScreenStatus::class,
        'last_heartbeat' => 'datetime',
    ];

    /**
     * The place that hosts the screen.
     */
    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    /**
     * Ads that are linked to the screen.
     */
    public function ads(): BelongsToMany
    {
        return $this->belongsToMany(Ad::class)
            ->withPivot('play_order')
            ->withTimestamps();
    }

    /**
     * Scheduling entries associated with the screen.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(AdSchedule::class);
    }

    /**
     * Status logs reported by the screen.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ScreenLog::class);
    }

    /**
     * Playback logs produced by the screen.
     */
    public function playbacks(): HasMany
    {
        return $this->hasMany(PlaybackLog::class);
    }
}
