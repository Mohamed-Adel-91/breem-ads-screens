<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class OrderStatusEnum extends Enum
{
    const PENDING   = 1;
    const FAILED    = 2;
    const PAID      = 3;
    const CANCELED  = 4;

    public static function getDescription($value): string
    {
        if ($value instanceof self) {
            $value = $value->value;
        }

        switch ($value) {
            case self::PENDING:
                return 'Pending (Order created but not paid)';
            case self::FAILED:
                return 'Failed (Payment failed)';
            case self::PAID:
                return 'Paid';
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
            self::FAILED   => self::getDescription(self::FAILED),
            self::PAID     => self::getDescription(self::PAID),
            self::CANCELED => self::getDescription(self::CANCELED),
        ];
    }
}
