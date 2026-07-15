<?php

declare(strict_types=1);

namespace App\Enums;

enum AdmissionType: string
{
    case NewStudent = 'new_student'; case Transfer = 'transfer'; case Returning = 'returning';
    public function label(): string { return match($this){self::NewStudent=>'Siswa Baru',self::Transfer=>'Pindahan',self::Returning=>'Kembali Bersekolah'}; }
}
