<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ServicesOrderStatusEnum extends Enum
{
    const PENDING   = 1;
    const APPROVED  = 2;
    const DECLINE   = 3;
    const CANCELED  = 4;

    public static function getDescription($value): string
    {
        if ($value instanceof self) {
            $value = $value->value;
        }

        switch ($value) {
            case self::PENDING:
                return 'Pending (Service created but not Approved)';
            case self::APPROVED:
                return 'APPROVED (Service Approved)';
            case self::DECLINE:
                return 'DECLINE (Service Rejected)';
            case self::CANCELED:
                return 'Canceled';
            default:
                return parent::getDescription($value);
        }
    }

    public static function asArrayWithDescriptions(): array
    {
        return [
            self::PENDING  => self::getDescription(self::PENDING),
            self::APPROVED => self::getDescription(self::APPROVED),
            self::DECLINE  => self::getDescription(self::DECLINE),
            self::CANCELED => self::getDescription(self::CANCELED),
        ];
    }
}
