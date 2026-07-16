<?php

declare(strict_types=1);

namespace App\Enums;

enum EmployeeDocumentType: string
{
    case IdentityCard = 'ktp';
    case FamilyCard = 'kartu_keluarga';
    case Diploma = 'ijazah_terakhir';
    case EducatorCertificate = 'sertifikat_pendidik';
    case AppointmentLetter = 'sk_pengangkatan';
    case Other = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::IdentityCard => 'KTP',
            self::FamilyCard => 'Kartu Keluarga',
            self::Diploma => 'Ijazah Terakhir',
            self::EducatorCertificate => 'Sertifikat Pendidik',
            self::AppointmentLetter => 'SK Pengangkatan',
            self::Other => 'Dokumen Lainnya',
        };
    }
}
