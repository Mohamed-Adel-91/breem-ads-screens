<?php

namespace App\Models;

use App\Support\TimeWindow;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class AdSchedule extends Model
{
    use HasFactory;

    /**
     * A schedule row is in exactly one of these states at any instant.
     */
    public const STATE_INACTIVE = 'inactive';
    public const STATE_UPCOMING = 'upcoming';
    public const STATE_CURRENT = 'current';
    public const STATE_ENDED = 'ended';

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
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * The ad that owns the schedule.
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    /**
     * The screen that the schedule is assigned to.
     */
    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }

    /**
     * The row's state at a given instant, for display.
     *
     * Reads loaded attributes only — no queries — so the admin schedule tables
     * can render a state badge per row without an N+1. The window comparison goes
     * through TimeWindow, so the badge and the device playlist can never disagree
     * about a boundary instant.
     *
     * This describes the ROW, not the ad's eligibility: a `current` row still
     * only makes the ad play if the ad's status and global window allow it.
     * AdSchedulerService remains the sole authority on eligibility.
     */
    public function currentState(?CarbonInterface $moment = null): string
    {
        if (! $this->is_active) {
            return self::STATE_INACTIVE;
        }

        $moment = $moment ? Carbon::instance($moment) : now();

        if ($this->start_time && $this->start_time->greaterThan($moment)) {
            return self::STATE_UPCOMING;
        }

        if (TimeWindow::contains($this->start_time, $this->end_time, $moment)) {
            return self::STATE_CURRENT;
        }

        return self::STATE_ENDED;
    }

    /**
     * Constrain the query to rows in the given state.
     *
     * The SQL mirrors currentState() exactly, including end-exclusivity, so
     * filtering a list can never contradict the badge rendered on its rows.
     */
    public function scopeInState(Builder $query, string $state, ?CarbonInterface $moment = null): Builder
    {
        $moment = $moment ? Carbon::instance($moment) : now();

        return match ($state) {
            self::STATE_INACTIVE => $query->where('is_active', false),
            self::STATE_UPCOMING => $query->where('is_active', true)
                ->where('start_time', '>', $moment),
            self::STATE_CURRENT => $query->where('is_active', true)
                ->where('start_time', '<=', $moment)
                ->where('end_time', '>', $moment),
            self::STATE_ENDED => $query->where('is_active', true)
                ->where('end_time', '<=', $moment),
            default => $query,
        };
    }

    /**
     * The states an admin may filter by.
     *
     * @return array<int, string>
     */
    public static function states(): array
    {
        return [
            self::STATE_CURRENT,
            self::STATE_UPCOMING,
            self::STATE_ENDED,
            self::STATE_INACTIVE,
        ];
    }
}
