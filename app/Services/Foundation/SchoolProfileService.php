<?php

declare(strict_types=1);

namespace App\Services\Foundation;

use App\Models\SchoolProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SchoolProfileService
{
    public const CACHE_KEY = 'school_profile.current';

    private ?SchoolProfile $resolved = null;

    public function current(): SchoolProfile
    {
        if ($this->resolved) {
            return $this->resolved;
        }

        if (! Schema::hasTable('school_profiles')) {
            return $this->resolved = $this->fallback();
        }

        return $this->resolved = Cache::rememberForever(self::CACHE_KEY, fn () => SchoolProfile::query()->oldest('id')->first() ?? $this->fallback());
    }

    public function update(User $actor, array $data): SchoolProfile
    {
        return DB::transaction(function () use ($actor, $data): SchoolProfile {
            $profile = $this->persisted();
            $old = $profile->only(array_keys($data));
            $profile->update([...$data, 'updated_by' => $actor->id]);
            activity('school-profile')->causedBy($actor)->performedOn($profile)->withProperties(['old' => $old, 'new' => $profile->only(array_keys($data))])->log('Memperbarui data profil madrasah.');
            $this->clearCache();

            return $profile;
        });
    }

    public function updateLeader(User $actor, array $data): SchoolProfile
    {
        return DB::transaction(function () use ($actor, $data): SchoolProfile {
            $profile = $this->persisted();
            $old = $profile->only(['head_name', 'head_nip']);
            $profile->update([...$data, 'updated_by' => $actor->id]);
            activity('school-profile')->causedBy($actor)->performedOn($profile)->withProperties(['old' => $old, 'new' => $profile->only(['head_name', 'head_nip'])])->log('Memperbarui data kepala madrasah.');
            $this->clearCache();

            return $profile;
        });
    }

    public function replaceLogo(User $actor, UploadedFile $logo): SchoolProfile
    {
        $profile = $this->persisted();
        $oldPath = $profile->logo_path;
        $newPath = $logo->store('school-profile/logos', 'public');

        try {
            DB::transaction(function () use ($actor, $profile, $newPath): void {
                $profile->update(['logo_path' => $newPath, 'updated_by' => $actor->id]);
                activity('school-profile')->causedBy($actor)->performedOn($profile)->withProperties(['logo_changed' => true])->log('Mengganti logo madrasah.');
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($newPath);
            throw $exception;
        }

        if ($oldPath && $oldPath !== $newPath) {
            Storage::disk('public')->delete($oldPath);
        }
        $this->clearCache();

        return $profile;
    }

    public function deleteLogo(User $actor): SchoolProfile
    {
        $profile = $this->persisted();
        $oldPath = $profile->logo_path;
        DB::transaction(function () use ($actor, $profile): void {
            $profile->update(['logo_path' => null, 'updated_by' => $actor->id]);
            activity('school-profile')->causedBy($actor)->performedOn($profile)->withProperties(['logo_removed' => true])->log('Menghapus logo madrasah.');
        });
        if ($oldPath) Storage::disk('public')->delete($oldPath);
        $this->clearCache();

        return $profile;
    }

    public function clearCache(): void
    {
        $this->resolved = null;
        Cache::forget(self::CACHE_KEY);
    }

    private function persisted(): SchoolProfile
    {
        return SchoolProfile::query()->oldest('id')->firstOrCreate([], $this->fallback()->only(['name', 'short_name', 'education_level', 'status', 'nsm', 'npsn']));
    }

    private function fallback(): SchoolProfile
    {
        return new SchoolProfile(['name' => config('emadrasah.name'), 'short_name' => config('emadrasah.short_name'), 'education_level' => config('emadrasah.level'), 'status' => config('emadrasah.status'), 'nsm' => config('emadrasah.nsm'), 'npsn' => config('emadrasah.npsn'), 'logo_path' => config('emadrasah.logo')]);
    }
}
