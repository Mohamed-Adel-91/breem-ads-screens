<?php

namespace App\Services\Screen;

use App\Models\ScreenDeviceCredential;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Single-use enforcement for request nonces.
 *
 * Backed by a unique index on (credential_id, nonce) rather than the cache: the
 * insert either succeeds or violates the constraint, which is atomic on every
 * supported database. Keying by credential means two devices may independently
 * pick the same random nonce without colliding.
 */
class DeviceReplayGuard
{
    /**
     * Record a nonce as used. Returns false when it has been seen before.
     */
    public function consume(ScreenDeviceCredential $credential, string $nonce): bool
    {
        try {
            DB::table('screen_request_nonces')->insert([
                'credential_id' => $credential->id,
                'nonce' => $nonce,
                'used_at' => now(),
            ]);
        } catch (QueryException $e) {
            // Any integrity violation on this table means "already used".
            return false;
        }

        $this->pruneOccasionally();

        return true;
    }

    /**
     * Drop nonces that can no longer be replayed because their timestamp would
     * already be outside the accepted window.
     */
    public function prune(): int
    {
        return DB::table('screen_request_nonces')
            ->where('used_at', '<', now()->subSeconds($this->retentionSeconds()))
            ->delete();
    }

    protected function pruneOccasionally(): void
    {
        // Cheap amortised cleanup; the table only ever needs one leeway window.
        if (random_int(1, 200) === 1) {
            $this->prune();
        }
    }

    protected function retentionSeconds(): int
    {
        // Twice the leeway leaves room for clock skew in both directions.
        return max(60, (int) config('services.screens.signature_leeway', 300) * 2);
    }
}
