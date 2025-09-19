<?php

namespace App\Services\Screen;

use App\Enums\ScreenStatus;
use App\Models\Screen;
use App\Models\ScreenLog;
use DateTimeInterface;
use Illuminate\Support\Carbon;

class HeartbeatService
{
    /**
     * Touch the screen heartbeat and create a corresponding log entry.
     *
     * @param  int|Screen  $screenId
     * @param  string|null  $deviceUid
     * @param  mixed  $currentAdCode
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

        $status = $this->normalizeStatus($options['status'] ?? null);
        $reportedAt = $this->normalizeDate($options['reported_at'] ?? null) ?? now();
        $lastHeartbeat = $this->normalizeDate($options['last_heartbeat'] ?? null) ?? now();

        $screen->forceFill([
            'status' => $status,
            'last_heartbeat' => $lastHeartbeat,
        ])->save();

        $log = $screen->logs()->create([
            'current_ad_code' => $options['current_ad_code'] ?? null,
            'status' => $status->value,
            'reported_at' => $reportedAt,
        ]);

        return [
            'screen' => $screen->fresh(),
            'log' => $log,
        ];
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
    protected function normalizeStatus($status): ScreenStatus
    {
        if ($status instanceof ScreenStatus) {
            return $status;
        }

        if (is_string($status)) {
            $enum = ScreenStatus::tryFrom($status);

            if ($enum) {
                return $enum;
            }
        }

        return ScreenStatus::Online;
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
