<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class CashMethodEnum extends Enum
{
    const VISA   = 1;
    const FAWRY  = 2;
    const WALLET = 3;

    public static function getDescription($value): string
    {
        if ($value instanceof self) {
            $value = $value->value;
        }

        return match ($value) {
            self::VISA   => 'Visa',
            self::FAWRY  => 'Fawry',
            self::WALLET => 'Wallet',
            default      => parent::getDescription($value),
        };
    }
}
