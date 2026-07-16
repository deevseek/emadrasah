<?php

declare(strict_types=1);

namespace App\Enums;

enum BtaqMaterialCategory: string
{
    case Reading = 'reading';
    case Writing = 'writing';
    case Tajwid = 'tajwid';
    case SurahMemorization = 'surah_memorization';
    case PrayerMemorization = 'prayer_memorization';
    case WorshipPractice = 'worship_practice';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Reading => 'Reading',
            self::Writing => 'Writing',
            self::Tajwid => 'Tajwid',
            self::SurahMemorization => 'Surah Memorization',
            self::PrayerMemorization => 'Prayer Memorization',
            self::WorshipPractice => 'Worship Practice',
            self::Other => 'Other',
        };
    }
}
