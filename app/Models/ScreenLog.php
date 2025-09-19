<?php

namespace App\Models;

use App\Enums\ScreenStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScreenLog extends Model
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
        'reported_at' => 'datetime',
    ];

    /**
     * The screen that produced the log entry.
     */
    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }
}
