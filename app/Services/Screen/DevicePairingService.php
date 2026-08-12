<?php

namespace App\Services\Screen;

use App\Models\Screen;
use App\Models\ScreenDeviceCredential;
use App\Models\ScreenPairingCode;
use App\Support\DeviceSignature;
use Illuminate\Support\Facades\DB;

/**
 * Owns the pairing lifecycle: issuing claim codes, claiming them, and revoking
 * the credentials they produced.
 *
 * Every state change runs inside a transaction with the screen row locked, so
 * two devices racing on the same code cannot both win.
 */
class DevicePairingService
{
    /**
     * Thrown reasons, surfaced to callers as translated messages.
     */
    public const REASON_UNKNOWN_SCREEN = 'unknown_screen';
    public const REASON_INVALID_CODE = 'invalid_code';
    public const REASON_EXPIRED_CODE = 'expired_code';
    public const REASON_ALREADY_PAIRED = 'already_paired';

    /**
     * Issue a fresh pairing code for a screen, superseding any live one.
     *
     * @return array{code: string, expires_at: \Illuminate\Support\Carbon}
     */
    public function issuePairingCode(Screen $screen, ?int $adminId = null): array
    {
        $plain = $this->generatePairingCode();

        $record = DB::transaction(function () use ($screen, $plain, $adminId) {
            // Retire any previous live code so the unique active index is free.
            ScreenPairingCode::query()
                ->where('screen_id', $screen->id)
                ->whereNotNull('active_screen_id')
                ->update(['active_screen_id' => null]);

            return ScreenPairingCode::create([
                'screen_id' => $screen->id,
                'active_screen_id' => $screen->id,
                'code_hash' => ScreenPairingCode::hashCode($plain),
                'expires_at' => now()->addSeconds($this->codeTtl()),
                'created_by' => $adminId,
            ]);
        });

        // The plaintext is returned here and never persisted.
        return ['code' => $plain, 'expires_at' => $record->expires_at];
    }

    /**
     * Claim a screen with a one-time pairing code and issue device credentials.
     *
     * @return array{screen: Screen, credential: ScreenDeviceCredential, token: string, hmac_secret: string}
     *
     * @throws PairingException
     */
    public function claim(string $screenCode, string $pairingCode, string $deviceUid): array
    {
        return DB::transaction(function () use ($screenCode, $pairingCode, $deviceUid) {
            $screen = Screen::query()
                ->where('code', $screenCode)
                ->lockForUpdate()
                ->first();

            if (! $screen) {
                throw new PairingException(self::REASON_UNKNOWN_SCREEN);
            }

            // A screen that already has a usable credential must be reset by an
            // administrator before it can be claimed again.
            if ($this->activeCredential($screen)) {
                throw new PairingException(self::REASON_ALREADY_PAIRED);
            }

            // active_screen_id is the "this is the current code" marker: issuing a
            // new code nulls it on the old rows, and consuming a code nulls it
            // too. Matching on it here is what makes regeneration actually
            // invalidate the superseded code — an administrator who regenerates
            // because a code leaked would otherwise leave the leaked one
            // claimable for the rest of its TTL.
            $candidate = ScreenPairingCode::query()
                ->where('screen_id', $screen->id)
                ->where('code_hash', ScreenPairingCode::hashCode($pairingCode))
                ->whereNotNull('active_screen_id')
                ->lockForUpdate()
                ->first();

            if (! $candidate || $candidate->isConsumed()) {
                throw new PairingException(self::REASON_INVALID_CODE);
            }

            if ($candidate->isExpired()) {
                throw new PairingException(self::REASON_EXPIRED_CODE);
            }

            // Consume atomically: the row must still be the live, unconsumed code
            // when we write, or a concurrent claim/regeneration beat us to it.
            $consumed = ScreenPairingCode::query()
                ->whereKey($candidate->id)
                ->whereNull('consumed_at')
                ->whereNotNull('active_screen_id')
                ->update(['consumed_at' => now(), 'active_screen_id' => null]);

            if ($consumed !== 1) {
                throw new PairingException(self::REASON_INVALID_CODE);
            }

            return $this->issueCredential($screen, $deviceUid);
        });
    }

    /**
     * Create credentials for a screen, replacing any previous ones.
     *
     * @return array{screen: Screen, credential: ScreenDeviceCredential, token: string, hmac_secret: string}
     */
    public function issueCredential(Screen $screen, ?string $deviceUid = null): array
    {
        $token = DeviceSignature::newToken();
        $secret = DeviceSignature::newSecret();

        $credential = DB::transaction(function () use ($screen, $deviceUid, $token, $secret) {
            $this->revokeActive($screen);

            $credential = ScreenDeviceCredential::create([
                'screen_id' => $screen->id,
                'active_screen_id' => $screen->id,
                'device_uid' => $deviceUid,
                'token_hash' => ScreenDeviceCredential::hashToken($token),
                'hmac_secret' => $secret,
                'issued_at' => now(),
            ]);

            if ($deviceUid !== null && $screen->device_uid !== $deviceUid) {
                // Identity metadata only; it grants nothing on its own.
                $screen->forceFill(['device_uid' => $deviceUid])->save();
            }

            return $credential;
        });

        return [
            'screen' => $screen->fresh(),
            'credential' => $credential,
            'token' => $token,
            'hmac_secret' => $secret,
        ];
    }

    /**
     * Revoke the screen's active credential, if any. Returns true when one was
     * actually revoked.
     */
    public function revokeActive(Screen $screen): bool
    {
        return ScreenDeviceCredential::query()
            ->where('screen_id', $screen->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now(), 'active_screen_id' => null]) > 0;
    }

    /**
     * Revoke credentials and retire any live pairing code.
     */
    public function resetDevice(Screen $screen): bool
    {
        return DB::transaction(function () use ($screen) {
            ScreenPairingCode::query()
                ->where('screen_id', $screen->id)
                ->whereNotNull('active_screen_id')
                ->update(['active_screen_id' => null]);

            return $this->revokeActive($screen);
        });
    }

    /**
     * The screen's usable credential, or null.
     */
    public function activeCredential(Screen $screen): ?ScreenDeviceCredential
    {
        $credential = ScreenDeviceCredential::query()
            ->where('screen_id', $screen->id)
            ->whereNull('revoked_at')
            ->latest('id')
            ->first();

        return $credential && $credential->isUsable() ? $credential : null;
    }

    /**
     * The screen's live, claimable pairing code record, or null.
     */
    public function livePairingCode(Screen $screen): ?ScreenPairingCode
    {
        $code = ScreenPairingCode::query()
            ->where('screen_id', $screen->id)
            ->whereNotNull('active_screen_id')
            ->latest('id')
            ->first();

        return $code && $code->isClaimable() ? $code : null;
    }

    protected function generatePairingCode(): string
    {
        // Unambiguous alphabet, grouped for reading aloud during installation.
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $chunks = [];

        for ($chunk = 0; $chunk < 3; $chunk++) {
            $part = '';
            for ($i = 0; $i < 4; $i++) {
                $part .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $chunks[] = $part;
        }

        return implode('-', $chunks);
    }

    protected function codeTtl(): int
    {
        return max(60, (int) config('services.screens.pairing_code_ttl', 900));
    }
}

