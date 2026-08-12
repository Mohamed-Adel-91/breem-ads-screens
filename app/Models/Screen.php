<?php

namespace App\Models;

use App\Enums\ScreenStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
     * The most recent log entry for this screen.
     *
     * Prefer this over eager-loading `logs` with a `limit(1)` constraint. That
     * pattern was long recorded as a bug — one combined child query, so only one
     * screen on the page got a latest log — and it genuinely was on older
     * Laravel. Laravel 11+ rewrites the limit into
     * `row_number() OVER (PARTITION BY screen_id ...)`, so it is no longer
     * wrong, but it relies on the framework quietly transforming the query and
     * hands Blade a one-element collection. This relation states the intent
     * directly and returns a model.
     *
     * Ordered by `reported_at` to match the log stream everywhere else, with the
     * primary key breaking ties between entries written in the same second.
     */
    public function latestLog(): HasOne
    {
        return $this->hasOne(ScreenLog::class)->ofMany([
            'reported_at' => 'max',
            'id' => 'max',
        ]);
    }

    /**
     * The newest unacknowledged offline event, if the screen has one.
     */
    public function openAlert(): HasOne
    {
        return $this->hasOne(ScreenLog::class)
            ->ofMany(
                ['reported_at' => 'max', 'id' => 'max'],
                fn ($query) => $query
                    ->where('status', ScreenStatus::Offline->value)
                    ->whereNull('acknowledged_at')
            );
    }

    /**
     * Playback logs produced by the screen.
     */
    public function playbacks(): HasMany
    {
        return $this->hasMany(PlaybackLog::class);
    }
}
