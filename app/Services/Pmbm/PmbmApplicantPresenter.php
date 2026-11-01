<?php

declare(strict_types=1);

namespace App\Services\Pmbm;

use App\Enums\Pmbm\ApplicantStatus;
use BackedEnum;
use Carbon\Carbon;
use DateTimeInterface;

class PmbmApplicantPresenter
{
    public function fieldLabel(string $field): string
    {
        return $this->labels()['fields'][$field] ?? 'Keterangan tambahan';
    }

    public function value(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (str_ends_with($field, 'birth_date') || $value instanceof DateTimeInterface) {
            return Carbon::parse($value)->locale('id')->translatedFormat('d F Y');
        }

        $group = preg_replace('/^(father|mother|guardian)_/', '', $field);
        if ($field === 'guardian_type') {
            $group = $field;
        }

        $value = $value instanceof BackedEnum ? $value->value : $value;

        return $this->labels()['values'][$group][(string) $value] ?? (string) $value;
    }

    public function status(?string $status): string
    {
        $label = ApplicantStatus::tryFrom($status ?? '')?->label() ?? 'Status belum tersedia';

        return str_replace('Fitting', 'Pengukuran seragam', $label);
    }

    public function documentName(string $code, array $names): string
    {
        return $names[$code] ?? match ($code) {
            'offline_registration_form' => 'Formulir pendaftaran luring',
            'birth_certificate', 'akta' => 'Akta kelahiran',
            'family_card', 'kk' => 'Kartu Keluarga (KK)',
            'photo' => 'Pasfoto',
            default => 'Dokumen persyaratan',
        };
    }

    private function labels(): array
    {
        return trans('pmbm', [], 'id');
    }
}
