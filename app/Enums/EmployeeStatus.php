<?php

declare(strict_types=1);

namespace App\Enums;

enum EmployeeStatus: string
{
    case CivilServant = 'pns';
    case GovernmentContract = 'pppk';
    case FoundationPermanentTeacher = 'gty';
    case NonPermanentTeacher = 'gtt';
    case FoundationPermanentEmployee = 'pegawai_tetap_yayasan';
    case NonPermanentEmployee = 'pegawai_tidak_tetap';
    case Honorary = 'honorer';
    case Permanent = 'tetap';
    case NonPermanent = 'tidak_tetap';
    case Contract = 'kontrak';
    case Other = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::CivilServant => 'PNS',
            self::GovernmentContract => 'PPPK',
            self::FoundationPermanentTeacher => 'GTY',
            self::NonPermanentTeacher => 'GTT',
            self::FoundationPermanentEmployee => 'Pegawai Tetap Yayasan',
            self::NonPermanentEmployee => 'Pegawai Tidak Tetap',
            self::Honorary => 'Honorer',
            self::Permanent => 'Tetap',
            self::NonPermanent => 'Tidak Tetap',
            self::Contract => 'Kontrak',
            self::Other => 'Lainnya',
        };
    }
}
