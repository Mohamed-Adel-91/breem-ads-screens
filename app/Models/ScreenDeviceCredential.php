<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Secret material proving a physical device may act as a given screen.
 *
 * The bearer token is stored only as a SHA-256 hash; the plaintext exists once,
 * in the pairing response. The signing secret is encrypted at rest because the
 * server must recover it to verify signatures.
 */
class ScreenDeviceCredential extends Model
{
    protected $fillable = [
        'screen_id',
        'active_screen_id',
        'device_uid',
        'token_hash',
        'hmac_secret',
        'issued_at',
        'expires_at',
        'revoked_at',
        'last_used_at',
    ];

    protected $casts = [
        'hmac_secret' => 'encrypted',
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    /**
     * Never serialise credential material, whatever the caller asks for.
     *
     * @var array<int, string>
     */
    protected $hidden = ['token_hash', 'hmac_secret'];

    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isUsable(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }

    /**
     * Hash a plaintext bearer token for storage and lookup.
     *
     * Device tokens are high-entropy random strings, so a fast one-way hash is
     * the right tool: it must be deterministic to look up, and there is no
     * low-entropy guess space for bcrypt to defend against.
     */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
