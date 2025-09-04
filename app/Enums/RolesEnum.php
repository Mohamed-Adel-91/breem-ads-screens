<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class RolesEnum extends Enum
{
    const SUPER_ADMIN = 1;
    const IT = 2;
    const MARKETING = 3;
    const SALES = 4;
    const RENTAL = 5;
    const HR = 6;

    public static function getDescription($value): string
    {
        if ($value instanceof self) {
            $value = $value->value;
        }

        switch ($value) {
            case self::SUPER_ADMIN:
                return 'Super Admin';
            case self::IT:
                return 'IT';
            case self::MARKETING:
                return 'Marketing';
            case self::SALES:
                return 'Sales';
            case self::RENTAL:
                return 'Rental';
            case self::HR:
                return 'HR';
            default:
                return parent::getDescription($value);
        }
    }

    public static function asArrayWithDescriptions(): array
    {
        return [
            self::SUPER_ADMIN => self::getDescription(self::SUPER_ADMIN),
            self::IT => self::getDescription(self::IT),
            self::MARKETING => self::getDescription(self::MARKETING),
            self::SALES => self::getDescription(self::SALES),
            self::RENTAL => self::getDescription(self::RENTAL),
            self::HR => self::getDescription(self::HR),
        ];
    }
}
