<?php
// app/Enums/SaudiCityEnum.php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class SaudiCityEnum extends Enum
{
    const RIYADH        = 1;  // الرياض
    const JEDDAH        = 2;  // جدة
    const MAKKAH        = 3;  // مكة المكرمة
    const MADINAH       = 4;  // المدينة المنورة
    const DAMMAM        = 5;  // الدمام
    const KHOBAR        = 6;  // الخبر
    const DHAHRAN       = 7;  // الظهران
    const QATIF         = 8;  // القطيف
    const JUBAIL        = 9;  // الجبيل
    const AL_AHSA       = 10; // الأحساء
    const TAIF          = 11; // الطائف
    const ABHA          = 12; // أبها
    const KHAMIS_MUSHAIT = 13; // خميس مشيط
    const JAZAN         = 14; // جازان
    const NAJRAN        = 15; // نجران
    const HAIL          = 16; // حائل
    const TABUK         = 17; // تبوك
    const AL_BAHA       = 18; // الباحة
    const ARAR          = 19; // عرعر
    const SAKAKA        = 20; // سكاكا (الجوف)
    const RAS_TANURA    = 21; // رأس تنورة
    const YANBU         = 22; // ينبع
    const AL_KHARJ      = 23; // الخرج
    const MAJMAAH       = 24; // المجمعة

    public static function getDescription($value): string
    {
        if ($value instanceof self) {
            $value = $value->value;
        }

        return match ($value) {
            self::RIYADH         => 'الرياض',
            self::JEDDAH         => 'جدة',
            self::MAKKAH         => 'مكة المكرمة',
            self::MADINAH        => 'المدينة المنورة',
            self::DAMMAM         => 'الدمام',
            self::KHOBAR         => 'الخبر',
            self::DHAHRAN        => 'الظهران',
            self::QATIF          => 'القطيف',
            self::JUBAIL         => 'الجبيل',
            self::AL_AHSA        => 'الأحساء',
            self::TAIF           => 'الطائف',
            self::ABHA           => 'أبها',
            self::KHAMIS_MUSHAIT => 'خميس مشيط',
            self::JAZAN          => 'جازان',
            self::NAJRAN         => 'نجران',
            self::HAIL           => 'حائل',
            self::TABUK          => 'تبوك',
            self::AL_BAHA        => 'الباحة',
            self::ARAR           => 'عرعر',
            self::SAKAKA         => 'سكاكا',
            self::RAS_TANURA     => 'رأس تنورة',
            self::YANBU          => 'ينبع',
            self::AL_KHARJ       => 'الخرج',
            self::MAJMAAH        => 'المجمعة',
            default              => parent::getDescription($value),
        };
    }

    public static function asArrayWithDescriptions(): array
    {
        return [
            self::RIYADH         => self::getDescription(self::RIYADH),
            self::JEDDAH         => self::getDescription(self::JEDDAH),
            self::MAKKAH         => self::getDescription(self::MAKKAH),
            self::MADINAH        => self::getDescription(self::MADINAH),
            self::DAMMAM         => self::getDescription(self::DAMMAM),
            self::KHOBAR         => self::getDescription(self::KHOBAR),
            self::DHAHRAN        => self::getDescription(self::DHAHRAN),
            self::QATIF          => self::getDescription(self::QATIF),
            self::JUBAIL         => self::getDescription(self::JUBAIL),
            self::AL_AHSA        => self::getDescription(self::AL_AHSA),
            self::TAIF           => self::getDescription(self::TAIF),
            self::ABHA           => self::getDescription(self::ABHA),
            self::KHAMIS_MUSHAIT => self::getDescription(self::KHAMIS_MUSHAIT),
            self::JAZAN          => self::getDescription(self::JAZAN),
            self::NAJRAN         => self::getDescription(self::NAJRAN),
            self::HAIL           => self::getDescription(self::HAIL),
            self::TABUK          => self::getDescription(self::TABUK),
            self::AL_BAHA        => self::getDescription(self::AL_BAHA),
            self::ARAR           => self::getDescription(self::ARAR),
            self::SAKAKA         => self::getDescription(self::SAKAKA),
            self::RAS_TANURA     => self::getDescription(self::RAS_TANURA),
            self::YANBU          => self::getDescription(self::YANBU),
            self::AL_KHARJ       => self::getDescription(self::AL_KHARJ),
            self::MAJMAAH        => self::getDescription(self::MAJMAAH),
        ];
    }
}
