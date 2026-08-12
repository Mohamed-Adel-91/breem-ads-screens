<?php

namespace App\Services\Screen;

use App\Enums\ScreenStatus;
use App\Models\Screen;
use App\Models\ScreenLog;
use App\Support\ScreenHealth;
use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * Owns every write to `screens.status` and `screens.last_heartbeat`.
 *
 * Two rules govern this class:
 *
 * 1. `last_heartbeat` is the moment the BREEM SERVER accepted a heartbeat. It is
 *    stamped with server time and is never taken from the device. A device
 *    cannot claim freshness it does not have, and no administrator action
 *    reaches it at all.
 * 2. A request that survived Phase 10 authentication proves the device is
 *    reachable right now. A heartbeat therefore cannot make the server believe
 *    the same device is offline — only silence can, via CheckScreenHealthJob.
 */
class HeartbeatService
{
    /**
     * Record a heartbeat from an authenticated device.
     *
     * @param  int|Screen  $screenId
     * @param  string|null  $deviceUid
     * @param  mixed  $currentAdCode  a string ad code, or an options array
     * @return array{screen: Screen, log: ScreenLog}|null
     */
    public function touch($screenId, $deviceUid, $currentAdCode = null): ?array
    {
        $screen = $screenId instanceof Screen
            ? $screenId
            : Screen::query()->find($screenId);

        if (! $screen) {
            return null;
        }

        if ($deviceUid && $screen->device_uid !== $deviceUid) {
            $screen->device_uid = $deviceUid;
        }

        $options = $this->normalizeOptions($currentAdCode);

        // Server receipt time. Deliberately not derived from any client value.
        $receivedAt = now();

        $status = $this->connectivityStatus($screen, $options['status'] ?? null);

        $screen->forceFill([
            'status' => $status,
            'last_heartbeat' => $receivedAt,
        ])->save();

        $log = $screen->logs()->create([
            'current_ad_code' => $options['current_ad_code'] ?? null,
            'status' => $status->value,
            'reported_at' => $this->telemetryTimestamp($options['reported_at'] ?? null, $receivedAt),
        ]);

        return [
            'screen' => $screen->fresh(),
            'log' => $log,
        ];
    }

    /**
     * Transition a silent screen to offline.
     *
     * Returns null when the screen is not eligible, which is what makes the
     * offline sweep idempotent: a screen that is already offline, is under
     * maintenance, or has heartbeated recently produces no write and no log.
     *
     * `last_heartbeat` is left exactly as it was — the whole point is that the
     * screen has NOT been heard from, so stamping it would destroy the evidence.
     */
    public function markOffline(Screen $screen): ?ScreenLog
    {
        if (! $this->isOfflineEligible($screen)) {
            return null;
        }

        $screen->forceFill(['status' => ScreenStatus::Offline])->save();

        return $screen->logs()->create([
            'status' => ScreenStatus::Offline->value,
            'reported_at' => now(),
            'current_ad_code' => null,
        ]);
    }

    /**
     * Only an online screen whose heartbeat has gone stale may be transitioned.
     *
     * Maintenance is excluded on purpose: it means operators have taken
     * ownership of the screen, so connectivity alerting is suppressed until they
     * hand it back. `last_heartbeat` still shows the staleness in the UI.
     */
    public function isOfflineEligible(Screen $screen): bool
    {
        return $screen->status === ScreenStatus::Online
            && ScreenHealth::isStale($screen->last_heartbeat);
    }

    /**
     * The authoritative status for a screen that just reported in.
     *
     * A device may declare `maintenance` — it is reachable but not serving, and
     * that is a real operational mode worth recording. Every other value,
     * including a device claiming `offline`, resolves to online: the request
     * that carried the claim is itself proof of reachability.
     *
     * Maintenance set by an administrator is sticky. A heartbeat refreshes
     * `last_heartbeat` but does not clear it; only an explicit Screen edit does.
     */
    protected function connectivityStatus(Screen $screen, $reported): ScreenStatus
    {
        if ($screen->status === ScreenStatus::Maintenance) {
            return ScreenStatus::Maintenance;
        }

        $claim = $this->normalizeStatus($reported);

        return $claim === ScreenStatus::Maintenance
            ? ScreenStatus::Maintenance
            : ScreenStatus::Online;
    }

    /**
     * The value stored in `screen_logs.reported_at`.
     *
     * This column orders the log stream and therefore drives the availability
     * timeline, so a device cannot be allowed to write an arbitrary instant into
     * it. The client value is clamped to the window the signed request could
     * legitimately have come from: never later than server receipt, never older
     * than the signature leeway that already bounds the request's own timestamp.
     */
    protected function telemetryTimestamp($reported, Carbon $receivedAt): Carbon
    {
        $value = $this->normalizeDate($reported);

        if (! $value) {
            return $receivedAt;
        }

        $leeway = max(0, (int) config('services.screens.signature_leeway', 300));
        $earliest = (clone $receivedAt)->subSeconds($leeway);

        if ($value->gt($receivedAt)) {
            return $receivedAt;
        }

        return $value->lt($earliest) ? $earliest : $value;
    }

    /**
     * Normalize the input options into a common structure.
     *
     * @param  mixed  $currentAdCode
     * @return array<string, mixed>
     */
    protected function normalizeOptions($currentAdCode): array
    {
        if (is_array($currentAdCode)) {
            return $currentAdCode;
        }

        return [
            'current_ad_code' => $currentAdCode,
        ];
    }

    /**
     * Normalize the status into an enum instance.
     */
    protected function normalizeStatus($status): ?ScreenStatus
    {
        if ($status instanceof ScreenStatus) {
            return $status;
        }

        return is_string($status) ? ScreenStatus::tryFrom($status) : null;
    }

    /**
     * Normalize the provided value into a Carbon instance.
     */
    protected function normalizeDate($value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_string($value)) {
            return Carbon::parse($value);
        }

        return null;
    }
}
