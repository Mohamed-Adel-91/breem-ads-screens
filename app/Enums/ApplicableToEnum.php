<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ApplicableToEnum extends Enum
{
    const INDIVIDUAL = 1;
    const COMPANY    = 2;
    const BOTH       = 3;

    public static function getDescription($value): string
    {
        if ($value instanceof self) {
            $value = $value->value;
        }

        return match ($value) {
            self::INDIVIDUAL => 'Individual',
            self::COMPANY    => 'Company',
            self::BOTH       => 'Individuals & Companies',
            default          => parent::getDescription($value),
        };
    }

    public static function asArrayWithDescriptions(): array
    {
        return [
            self::INDIVIDUAL => self::getDescription(self::INDIVIDUAL),
            self::COMPANY    => self::getDescription(self::COMPANY),
            self::BOTH       => self::getDescription(self::BOTH),
        ];
    }
}
