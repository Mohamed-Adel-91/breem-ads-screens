<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScreenPairingCode extends Model
{
    protected $fillable = [
        'screen_id',
        'active_screen_id',
        'code_hash',
        'expires_at',
        'consumed_at',
        'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    /** @var array<int, string> */
    protected $hidden = ['code_hash'];

    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isClaimable(): bool
    {
        return ! $this->isConsumed() && ! $this->isExpired();
    }

    public static function hashCode(string $code): string
    {
        return hash('sha256', $code);
    }
}
