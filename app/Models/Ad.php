<?php

namespace App\Models;

use App\Enums\AdStatus;
use App\Services\Screen\AdSchedulerService;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Concerns\HasPivotEvents;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Ad extends Model
{
    use HasFactory;
    use HasTranslations;
    use HasPivotEvents;

    public const UPLOAD_FOLDER = 'upload/ads';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public array $translatable = ['title', 'description'];

    protected static function booted(): void
    {
        static::pivotAttached(function (Ad $ad, string $relationName, array $pivotIds, array $pivotIdsAttributes): void {
            if ($relationName === 'screens') {
                app(AdSchedulerService::class)->forgetMany($pivotIds);
            }
        });

        static::pivotUpdated(function (Ad $ad, string $relationName, array $pivotIds, array $pivotIdsAttributes): void {
            if ($relationName === 'screens') {
                app(AdSchedulerService::class)->forgetMany($pivotIds);
            }
        });

        static::pivotDetached(function (Ad $ad, string $relationName, array $pivotIds): void {
            if ($relationName === 'screens') {
                app(AdSchedulerService::class)->forgetMany($pivotIds);
            }
        });
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
    ];

    /**
     * Accessor to resolve the full URL for the creative file.
     */
    public function getFileUrlAttribute(): ?string
    {
        $path = $this->file_path;

        if (!$path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return asset($path);
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
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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
