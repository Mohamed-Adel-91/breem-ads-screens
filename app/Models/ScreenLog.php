<?php

namespace App\Models;

use App\Enums\ScreenStatus;
use App\Support\Retention;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScreenLog extends Model
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
        'status' => ScreenStatus::class,
        'reported_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    /**
     * Rows eligible for retention pruning.
     *
     * This table is the fleet's heartbeat telemetry: one row per heartbeat plus one
     * per offline transition, so roughly `fleet size × 1440` rows a day at the
     * default 60-second cadence. That volume is intentional and the writes are not
     * reduced — retention is what bounds it.
     *
     * Returns a query matching **nothing** unless a positive
     * `SCREEN_LOG_RETENTION_DAYS` is configured, so the default posture deletes
     * nothing at all. The comparison is `<` on `reported_at`, which has its own index
     * (see the Phase 14 migration) so the delete does not scan the table, and it
     * cannot touch a row newer than the cutoff.
     */
    public function prunable(): Builder
    {
        $cutoff = Retention::cutoffFor(Retention::SCREEN_LOGS);

        if ($cutoff === null) {
            return static::query()->whereRaw('1 = 0');
        }

        return static::query()->where('reported_at', '<', $cutoff);
    }

    /**
     * The screen that produced the log entry.
     */
    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }

    /**
     * The administrator who acknowledged this event, if anyone has.
     */
    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'acknowledged_by');
    }

    /**
     * An offline event is the thing an administrator acknowledges. Online and
     * maintenance entries are not alerts.
     */
    public function isAlert(): bool
    {
        return $this->status === ScreenStatus::Offline;
    }

    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }
}
