<?php

namespace App\Models;

use App\Support\Retention;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Admin;

class Report extends Model
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
        'filters' => 'array',
        'data' => 'array',
    ];

    /**
     * Rows eligible for retention pruning.
     *
     * A report is an immutable snapshot, so pruning one destroys the only remaining
     * copy of figures whose source logs may already be gone. Off unless a positive
     * `REPORT_RETENTION_DAYS` is set. `created_at` carries its own index (Phase 14
     * migration), which the index page's `latest('created_at')` ordering also uses.
     */
    public function prunable(): Builder
    {
        $cutoff = Retention::cutoffFor(Retention::REPORTS);

        if ($cutoff === null) {
            return static::query()->whereRaw('1 = 0');
        }

        return static::query()->where('created_at', '<', $cutoff);
    }

    /**
     * Get the admin who generated the report.
     */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'generated_by');
    }
}
