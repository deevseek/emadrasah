<?php

declare(strict_types=1);

namespace App\Services\Pmbm;

use App\Models\Pmbm\PmbmSetting;
use Illuminate\Support\Collection;

class PmbmPublicInformationService
{
    public function activeSetting(): ?PmbmSetting
    {
        return PmbmSetting::with('academicYear')->where('enabled', true)->latest()->first();
    }

    public function registrationState(?PmbmSetting $setting): string
    {
        if (! $setting || ! $setting->online_registration_enabled) return 'closed';
        if ($setting->registration_open_at && now()->lt($setting->registration_open_at)) return 'upcoming';
        if ($setting->registration_close_at && now()->gt($setting->registration_close_at)) return 'closed';
        return 'open';
    }

    public function statusLabel(?PmbmSetting $setting): string
    {
        return match ($this->registrationState($setting)) {
            'open' => 'Pendaftaran Dibuka',
            'upcoming' => 'Pendaftaran Dibuka mulai '.($setting?->registration_open_at?->translatedFormat('j F Y') ?? 'sesuai pengumuman'),
            default => $setting?->registration_open_at && now()->lt($setting->registration_open_at) ? 'Pendaftaran Online Belum Dibuka' : 'Pendaftaran Online Telah Ditutup',
        };
    }

    /** @return Collection<int, array{name:string,male:int|null,female:int|null}> */
    public function fees(?PmbmSetting $setting): Collection
    {
        return collect($setting?->fee_items ?? [])->map(fn ($item) => [
            'name' => $item['name'] ?? 'Biaya PMBM',
            'male' => isset($item['male']) ? (int) $item['male'] : (isset($item['amount']) ? (int) $item['amount'] : null),
            'female' => isset($item['female']) ? (int) $item['female'] : (isset($item['amount']) ? (int) $item['amount'] : null),
        ]);
    }

    /** @return Collection<int, array{title:string,description:string}> */
    public function schedules(?PmbmSetting $setting): Collection
    {
        return collect($setting?->schedule_items ?? [])->map(fn ($item) => [
            'title' => $item['title'] ?? 'Jadwal PMBM',
            'description' => $item['description'] ?? '',
        ]);
    }
}
