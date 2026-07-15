<?php

declare(strict_types=1);

namespace App\Enums;

enum EmploymentType: string
{
    case Principal = 'kepala_madrasah';
    case ClassTeacher = 'guru_kelas';
    case SubjectTeacher = 'guru_mata_pelajaran';
    case BtaqTeacher = 'guru_btaq';
    case FullDayTeacher = 'guru_full_day';
    case Administration = 'tata_usaha';
    case Treasurer = 'bendahara';
    case Operator = 'operator';
    case Other = 'lainnya';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }
}
