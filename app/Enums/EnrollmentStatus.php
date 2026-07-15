<?php

declare(strict_types=1);

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Active='active'; case Promoted='promoted'; case Retained='retained'; case Transferred='transferred'; case Graduated='graduated'; case Withdrawn='withdrawn';
    public function label(): string { return match($this){self::Active=>'Aktif',self::Promoted=>'Naik Kelas',self::Retained=>'Tinggal Kelas',self::Transferred=>'Pindah Kelas',self::Graduated=>'Lulus',self::Withdrawn=>'Keluar'}; }
}
