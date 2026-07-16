<?php

declare(strict_types=1);

namespace App\Enums;

enum StudentDocumentType: string
{
    case BirthCertificate = 'birth_certificate';
    case FamilyCard = 'family_card';
    case GuardianIdentityCard = 'guardian_identity_card';
    case IdentityCard = 'identity_card';
    case IndonesiaSmartCard = 'indonesia_smart_card';
    case WelfareFamilyCard = 'welfare_family_card';
    case BpjsKis = 'bpjs_kis';
    case PreviousReport = 'previous_report';
    case GraduationCertificate = 'graduation_certificate';
    case PreviousDiploma = 'previous_diploma';
    case TransferLetter = 'transfer_letter';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BirthCertificate => 'Akta Kelahiran', self::FamilyCard => 'Kartu Keluarga', self::GuardianIdentityCard, self::IdentityCard => 'KTP Orang Tua/Wali',
            self::IndonesiaSmartCard => 'Kartu Indonesia Pintar', self::WelfareFamilyCard => 'Kartu Keluarga Sejahtera', self::BpjsKis => 'Kartu BPJS/KIS',
            self::PreviousReport => 'Rapor Sebelumnya', self::GraduationCertificate, self::PreviousDiploma => 'Ijazah Sebelumnya', self::TransferLetter => 'Surat Pindah', self::Other => 'Dokumen Lainnya',
        };
    }
}
