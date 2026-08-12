<?php

namespace App\Models;

use App\Support\Retention;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaybackLog extends Model
{
    use HasFactory;
    use MassPrunable;

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
        'played_at' => 'datetime',
        'duration' => 'integer',
        'extra' => 'array',
    ];

    /**
     * Rows eligible for retention pruning.
     *
     * **This table is proof-of-play evidence.** It is the record of which
     * advertisement actually appeared on which screen and for how long, which is
     * commercial and potentially contractual data — deleting it is not the same kind
     * of act as deleting heartbeat telemetry. It can also grow faster than
     * `screen_logs`, because a screen submits one entry per item played rather than
     * one per minute.
     *
     * So retention here is **off until someone who knows the commercial requirement
     * turns it on**: with no positive `PLAYBACK_LOG_RETENTION_DAYS` this matches
     * nothing. The `<` comparison on the indexed `played_at` column cannot reach a
     * row newer than the cutoff.
     */
    public function prunable(): Builder
    {
        $cutoff = Retention::cutoffFor(Retention::PLAYBACK_LOGS);

        if ($cutoff === null) {
            return static::query()->whereRaw('1 = 0');
        }

        return static::query()->where('played_at', '<', $cutoff);
    }

    /**
     * The screen on which the playback occurred.
     */
    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }

    /**
     * The ad that was played back.
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }
}
