<?php

declare(strict_types=1);

namespace App\Enums;

enum StudentDocumentType: string
{
    case BirthCertificate='birth_certificate'; case FamilyCard='family_card'; case IdentityCard='identity_card'; case PreviousReport='previous_report'; case TransferLetter='transfer_letter'; case GraduationCertificate='graduation_certificate'; case Other='other';
    public function label(): string { return match($this){self::BirthCertificate=>'Akta Kelahiran',self::FamilyCard=>'Kartu Keluarga',self::IdentityCard=>'Kartu Identitas',self::PreviousReport=>'Rapor Sebelumnya',self::TransferLetter=>'Surat Pindah',self::GraduationCertificate=>'Ijazah/SKL',self::Other=>'Lainnya'}; }
}
