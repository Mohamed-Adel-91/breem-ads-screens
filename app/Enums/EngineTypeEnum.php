<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class EngineTypeEnum extends Enum
{
    const PETROL    = 1;
    const DIESEL    = 2;
    const HYBRID    = 3;
    const ELECTRIC  = 4;

    public static function getDescription($value): string
    {
        if ($value instanceof self) {
            $value = $value->value;
        }

        return match ($value) {
            self::PETROL   => 'بنزين',
            self::DIESEL   => 'ديزل',
            self::HYBRID   => 'هجين',
            self::ELECTRIC => 'كهرباء',
            default        => parent::getDescription($value),
        };
    }

    public static function asArrayWithDescriptions(): array
    {
        return [
            self::PETROL   => self::getDescription(self::PETROL),
            self::DIESEL   => self::getDescription(self::DIESEL),
            self::HYBRID   => self::getDescription(self::HYBRID),
            self::ELECTRIC => self::getDescription(self::ELECTRIC),
        ];
    }
}
