<?php

declare(strict_types=1);

namespace App\Services\Foundation;

use App\Models\SchoolProfile;

class SchoolProfileService
{
    public function current(): SchoolProfile
    {
        return SchoolProfile::query()->firstOrCreate([], [
            'school_name' => config('app.name', 'E-Madrasah'),
            'timezone' => 'Asia/Jakarta',
        ]);
    }

    public function isComplete(?SchoolProfile $profile = null): bool
    {
        $profile ??= $this->current();

        return collect(['school_name', 'nsm', 'npsn', 'address', 'village', 'district', 'city', 'province', 'postal_code', 'phone', 'email', 'principal_name', 'timezone'])
            ->every(fn (string $field): bool => filled($profile->{$field}));
    }
}
