<?php

namespace App\Enums;

/**
 * The advertisement lifecycle.
 *
 * These five values are the pre-existing domain; Phase 13 added no state. What it
 * added is the rule that a status may only change along a declared edge. Before
 * that, `status` was a free select on the ad form, so any value could be written
 * over any other and "approval" was whatever the last person to save the form
 * chose.
 *
 * Only `Active` plays. `AdSchedulerService` tests `status === AdStatus::Active`
 * and nothing else, which is why approval and going live are two different edges:
 * `Approved` means "cleared for broadcast", `Active` means "broadcasting".
 */
enum AdStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Active = 'active';
    case Expired = 'expired';

    /**
     * The lifecycle actions an approver may take.
     */
    public const ACTION_APPROVE = 'approve';
    public const ACTION_REJECT = 'reject';
    public const ACTION_PUBLISH = 'publish';
    public const ACTION_UNPUBLISH = 'unpublish';
    public const ACTION_EXPIRE = 'expire';

    /**
     * Every action name, for validation.
     *
     * @return array<int, string>
     */
    public static function actions(): array
    {
        return [
            self::ACTION_APPROVE,
            self::ACTION_REJECT,
            self::ACTION_PUBLISH,
            self::ACTION_UNPUBLISH,
            self::ACTION_EXPIRE,
        ];
    }

    /**
     * The actions permitted from this status, mapped to the status they produce.
     *
     * An action absent from this map is refused — a status pair is never allowed
     * merely because both values exist in the enum.
     *
     *   pending   approve→approved   reject→rejected
     *   approved  publish→active     reject→rejected    expire→expired
     *   rejected  approve→approved
     *   active    unpublish→approved reject→rejected    expire→expired
     *   expired   approve→approved
     *
     * `pending` has no `publish` edge on purpose: going live without review is the
     * bypass this map exists to prevent. `rejected` and `expired` can be approved
     * again so a corrected or finished creative has a way back — otherwise an
     * expired ad would be stranded, since editing it only ever returns it to
     * `pending`.
     *
     * @return array<string, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [
                self::ACTION_APPROVE => self::Approved,
                self::ACTION_REJECT => self::Rejected,
            ],
            self::Approved => [
                self::ACTION_PUBLISH => self::Active,
                self::ACTION_REJECT => self::Rejected,
                self::ACTION_EXPIRE => self::Expired,
            ],
            self::Rejected => [
                self::ACTION_APPROVE => self::Approved,
            ],
            self::Active => [
                // Taking a live ad off air, either reversibly or for good.
                self::ACTION_UNPUBLISH => self::Approved,
                self::ACTION_REJECT => self::Rejected,
                self::ACTION_EXPIRE => self::Expired,
            ],
            self::Expired => [
                self::ACTION_APPROVE => self::Approved,
            ],
        };
    }

    /**
     * May this action be taken from this status?
     */
    public function allows(string $action): bool
    {
        return array_key_exists($action, $this->allowedTransitions());
    }

    /**
     * The status this action produces, or null when the edge does not exist.
     */
    public function resultOf(string $action): ?self
    {
        return $this->allowedTransitions()[$action] ?? null;
    }

    /**
     * Is this status the one the device playlist will actually play?
     *
     * The single place that answers "live?" for presentation. Eligibility itself
     * stays with AdSchedulerService.
     */
    public function isLive(): bool
    {
        return $this === self::Active;
    }

    /**
     * Has this ad passed creative review?
     *
     * Used to decide whether a playback-relevant edit has to send it back for
     * re-approval.
     */
    public function isReviewed(): bool
    {
        return $this === self::Approved || $this === self::Active;
    }
}
