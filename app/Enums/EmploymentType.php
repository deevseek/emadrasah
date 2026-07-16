<?php

declare(strict_types=1);

namespace App\Enums;

enum EmploymentType: string
{
    case Principal = 'kepala_madrasah';
    case ClassTeacher = 'guru_kelas';
    case SubjectTeacher = 'guru_mata_pelajaran';
    case BtaqTeacher = 'guru_btaq';
    case Administration = 'tata_usaha';
    case Operator = 'operator';
    case Treasurer = 'bendahara';
    case EducationStaff = 'tenaga_kependidikan';
    case FullDayTeacher = 'guru_full_day';
    case Other = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::Principal => 'Kepala Madrasah',
            self::ClassTeacher => 'Guru Kelas',
            self::SubjectTeacher => 'Guru Mata Pelajaran',
            self::BtaqTeacher => 'Guru BTAQ',
            self::Administration => 'Tata Usaha',
            self::Operator => 'Operator',
            self::Treasurer => 'Bendahara',
            self::EducationStaff => 'Tenaga Kependidikan',
            self::FullDayTeacher => 'Guru Full Day',
            self::Other => 'Pegawai Lainnya',
        };
    }
}
