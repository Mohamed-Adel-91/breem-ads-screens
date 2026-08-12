<?php

namespace App\Models;

use App\Enums\AdStatus;
use App\Services\Screen\AdSchedulerService;
use App\Support\AdValidity;
use App\Support\MediaUrl;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

class Ad extends Model
{
    use HasFactory;
    use HasTranslations;

    public const UPLOAD_FOLDER = 'upload/ads';

    /**
     * The attributes that are mass assignable.
     *
     * Switched from `$guarded = []` in Phase 13. Every column an application call
     * site legitimately writes is listed; the timestamps and `id` are not, so a
     * stray request field can no longer reach them. Note that being fillable is not
     * the same as being settable from a form: `status`, `approved_by_admin_id` and
     * `approved_at` are removed from the ad Form Requests entirely and are only
     * written by the lifecycle transition action.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'file_path',
        'file_type',
        'duration_seconds',
        'status',
        'created_by',
        'created_by_admin_id',
        'approved_by',
        'approved_by_admin_id',
        'approved_at',
        'start_date',
        'end_date',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public array $translatable = ['title', 'description'];

    /**
     * Attributes whose change makes the creative a different thing to play, and
     * therefore requires re-approval.
     *
     * Title and description are absent deliberately: they are not in the device
     * manifest, so editing them changes nothing a viewer sees on a screen.
     * Assignment, schedules and play order are absent because they are not ad
     * attributes at all — they carry their own authorization.
     *
     * @var array<int, string>
     */
    public const PLAYBACK_RELEVANT_ATTRIBUTES = [
        'file_path',
        'file_type',
        'duration_seconds',
        'start_date',
        'end_date',
    ];

    /**
     * Flush cached playlists for the given screen identifiers.
     */
    public function flushScreensCache(?iterable $screenIds = null): void
    {
        $ids = [];

        if (is_null($screenIds)) {
            $ids = $this->screens()->pluck('screens.id')->all();
        } else {
            foreach ($screenIds as $id) {
                if ($id) {
                    $ids[] = $id;
                }
            }
        }

        if (!empty($ids)) {
            app(AdSchedulerService::class)->forgetMany(array_unique($ids));
        }
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'title' => 'array',
        'description' => 'array',
        'status' => AdStatus::class,
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * The inclusive instant this ad becomes valid, or null when unbounded.
     *
     * Read through App\Support\AdValidity so the admin display and the device
     * playlist can never disagree about the window.
     */
    public function validFrom(): ?Carbon
    {
        return AdValidity::startsAt($this->start_date);
    }

    /**
     * The exclusive instant this ad stops being valid, or null when unbounded.
     *
     * For a date-only `end_date` this is the following midnight, so the ad plays
     * throughout the day the operator selected.
     */
    public function validBefore(): ?Carbon
    {
        return AdValidity::endsBefore($this->end_date);
    }

    /**
     * Is the ad inside its own global validity window at this moment?
     */
    public function isWithinValidity(?Carbon $moment = null): bool
    {
        return AdValidity::contains($this->start_date, $this->end_date, $moment ?? now());
    }

    /**
     * Accessor to resolve the full URL for the creative file.
     */
    public function getFileUrlAttribute(): ?string
    {
        return MediaUrl::resolve($this->file_path);
    }

    /**
     * Resolve a file path or URL into an absolute URL for playback.
     */
    public static function resolveFileUrl(?string $path): ?string
    {
        return MediaUrl::resolve($path);
    }

    /**
     * Get the schedules assigned to the ad.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(AdSchedule::class);
    }

    /**
     * Get the screens linked to the ad.
     */
    public function screens(): BelongsToMany
    {
        return $this->belongsToMany(Screen::class)
            ->withPivot('play_order')
            ->withTimestamps();
    }

    /**
     * Get the user that created the ad.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user that approved the ad.
     *
     * LEGACY. `approved_by` is a foreign key to `users`, but approvals are made by
     * `admins`. The column and its historical values are preserved; the authoritative
     * approver for anything approved from Phase 13 onwards is
     * {@see Ad::approverAdmin()}.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * The admin who created the ad — the actor domain that operates the dashboard.
     *
     * Null for rows created before Phase 13: there is no safe mapping from the
     * legacy `created_by` user id to an admin.
     */
    public function creatorAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    /**
     * The admin who approved the ad, alongside `approved_at`.
     */
    public function approverAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id');
    }

    /**
     * Get playback logs related to the ad.
     */
    public function playbacks(): HasMany
    {
        return $this->hasMany(PlaybackLog::class);
    }

    /**
     * Scope ads that are pending approval.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', AdStatus::Pending->value);
    }

    /**
     * Scope ads that have been approved.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', AdStatus::Approved->value);
    }

    /**
     * Scope ads that are rejected.
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', AdStatus::Rejected->value);
    }

    /**
     * Scope ads that are currently active.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', AdStatus::Active->value);
    }

    /**
     * Scope ads that have already expired.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', AdStatus::Expired->value);
    }

    /**
     * Scope ads that are active within a given period.
     */
    public function scopeActiveIn(Builder $query, Carbon|DateTimeInterface|string $start, Carbon|DateTimeInterface|string|null $end = null): Builder
    {
        $startAt = Carbon::parse($start);
        $endAt = $end ? Carbon::parse($end) : $startAt;

        return $query->where('status', AdStatus::Active->value)
            ->where(function (Builder $builder) use ($endAt) {
                $builder->whereNull('start_date')
                    ->orWhere('start_date', '<=', $endAt);
            })
            ->where(function (Builder $builder) use ($startAt) {
                $builder->whereNull('end_date')
                    ->orWhere('end_date', '>=', $startAt);
            });
    }

    /**
     * Scope ads that are expiring within the provided threshold.
     */
    public function scopeExpiringSoon(Builder $query, Carbon|DateTimeInterface|string|null $threshold = null): Builder
    {
        $now = now();
        $expiresBy = $threshold ? Carbon::parse($threshold) : (clone $now)->addDay();

        return $query->where('status', AdStatus::Active->value)
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [$now, $expiresBy]);
    }
}
