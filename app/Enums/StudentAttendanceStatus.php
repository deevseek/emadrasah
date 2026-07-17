<?php

declare(strict_types=1);

namespace App\Enums;

enum StudentAttendanceStatus: string
{
    case Present = 'hadir'; case Permission = 'izin'; case Sick = 'sakit'; case Alpha = 'alpha'; case Late = 'terlambat'; case EarlyLeave = 'pulang_lebih_awal'; case Duty = 'dinas'; case Unscheduled = 'tidak_dijadwalkan';
    public function label(): string { return match($this){self::Present=>'Hadir',self::Permission=>'Izin',self::Sick=>'Sakit',self::Alpha=>'Alpha',self::Late=>'Terlambat',self::EarlyLeave=>'Pulang Lebih Awal',self::Duty=>'Dinas/Kegiatan Madrasah',self::Unscheduled=>'Tidak Dijadwalkan'}; }
    public static function options(): array { return collect(self::cases())->mapWithKeys(fn($c)=>[$c->value=>$c->label()])->all(); }
}
