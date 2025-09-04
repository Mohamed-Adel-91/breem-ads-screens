<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PaymentTypeEnum extends Enum
{
    const CASH        = 1;
    const INSTALLMENT = 2;

    public static function getDescription($value): string
    {
        if ($value instanceof self) {
            $value = $value->value;
        }

        return match ($value) {
            self::CASH        => 'Cash',
            self::INSTALLMENT => 'Installment',
            default           => parent::getDescription($value),
        };
    }
}
