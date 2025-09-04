<?php
// app/Enums/CityEnum.php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class CityEnum extends Enum
{
    const CAIRO               = 1;  // القاهرة
    const GIZA                = 2;  // الجيزة
    const ALEXANDRIA          = 3;  // الإسكندرية
    const DOKHLIYA            = 4;  // الدقهلية
    const BEHERA              = 5;  // البحيرة
    const GHARBIA             = 6;  // الغربية
    const SHARKIA             = 7;  // الشرقية
    const MONUFIA             = 8;  // المنوفية
    const QALYUBIA            = 9;  // القليوبية
    const KAFR_EL_SHEIKH      = 10; // كفر الشيخ
    const DAMIETTA            = 11; // دمياط
    const BANI_SUEF           = 12; // بني سويف
    const FAYOUM              = 13; // الفيوم
    const MINYA               = 14; // المنيا
    const ASSIUT              = 15; // أسيوط
    const SUHAG               = 16; // سوهاج
    const QENA                = 17; // قنا
    const LUXOR               = 18; // الأقصر
    const ASWAN               = 19; // أسوان
    const RED_SEA             = 20; // البحر الأحمر
    const NEW_VALLEY          = 21; // الوادي الجديد
    const MATROUH             = 22; // مطروح
    const NORTH_SINAI         = 23; // شمال سيناء
    const SOUTH_SINAI         = 24; // جنوب سيناء
    const ISMAILIA            = 25; // الإسماعيلية
    const SUEZ                = 26; // السويس
    const PORT_SAID           = 27; // بورسعيد

    public static function getDescription($value): string
    {
        if ($value instanceof self) {
            $value = $value->value;
        }

        return match ($value) {
            self::CAIRO           => 'القاهرة',
            self::GIZA            => 'الجيزة',
            self::ALEXANDRIA      => 'الإسكندرية',
            self::DOKHLIYA        => 'الدقهلية',
            self::BEHERA          => 'البحيرة',
            self::GHARBIA         => 'الغربية',
            self::SHARKIA         => 'الشرقية',
            self::MONUFIA         => 'المنوفية',
            self::QALYUBIA        => 'القليوبية',
            self::KAFR_EL_SHEIKH  => 'كفر الشيخ',
            self::DAMIETTA        => 'دمياط',
            self::BANI_SUEF       => 'بني سويف',
            self::FAYOUM          => 'الفيوم',
            self::MINYA           => 'المنيا',
            self::ASSIUT          => 'أسيوط',
            self::SUHAG           => 'سوهاج',
            self::QENA            => 'قنا',
            self::LUXOR           => 'الأقصر',
            self::ASWAN           => 'أسوان',
            self::RED_SEA         => 'البحر الأحمر',
            self::NEW_VALLEY      => 'الوادي الجديد',
            self::MATROUH         => 'مطروح',
            self::NORTH_SINAI     => 'شمال سيناء',
            self::SOUTH_SINAI     => 'جنوب سيناء',
            self::ISMAILIA        => 'الإسماعيلية',
            self::SUEZ            => 'السويس',
            self::PORT_SAID       => 'بورسعيد',
            default               => parent::getDescription($value),
        };
    }

    public static function asArrayWithDescriptions(): array
    {
        return [
            self::CAIRO           => self::getDescription(self::CAIRO),
            self::GIZA            => self::getDescription(self::GIZA),
            self::ALEXANDRIA      => self::getDescription(self::ALEXANDRIA),
            self::DOKHLIYA        => self::getDescription(self::DOKHLIYA),
            self::BEHERA          => self::getDescription(self::BEHERA),
            self::GHARBIA         => self::getDescription(self::GHARBIA),
            self::SHARKIA         => self::getDescription(self::SHARKIA),
            self::MONUFIA         => self::getDescription(self::MONUFIA),
            self::QALYUBIA        => self::getDescription(self::QALYUBIA),
            self::KAFR_EL_SHEIKH  => self::getDescription(self::KAFR_EL_SHEIKH),
            self::DAMIETTA        => self::getDescription(self::DAMIETTA),
            self::BANI_SUEF       => self::getDescription(self::BANI_SUEF),
            self::FAYOUM          => self::getDescription(self::FAYOUM),
            self::MINYA           => self::getDescription(self::MINYA),
            self::ASSIUT          => self::getDescription(self::ASSIUT),
            self::SUHAG           => self::getDescription(self::SUHAG),
            self::QENA            => self::getDescription(self::QENA),
            self::LUXOR           => self::getDescription(self::LUXOR),
            self::ASWAN           => self::getDescription(self::ASWAN),
            self::RED_SEA         => self::getDescription(self::RED_SEA),
            self::NEW_VALLEY      => self::getDescription(self::NEW_VALLEY),
            self::MATROUH         => self::getDescription(self::MATROUH),
            self::NORTH_SINAI     => self::getDescription(self::NORTH_SINAI),
            self::SOUTH_SINAI     => self::getDescription(self::SOUTH_SINAI),
            self::ISMAILIA        => self::getDescription(self::ISMAILIA),
            self::SUEZ            => self::getDescription(self::SUEZ),
            self::PORT_SAID       => self::getDescription(self::PORT_SAID),
        ];
    }
}
