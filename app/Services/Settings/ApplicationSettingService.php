<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\ApplicationSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ApplicationSettingService
{
    public const CACHE_KEY = 'application_settings.all';

    public const DEFAULTS = [
        'app_name' => 'e-Madrasah', 'app_short_name' => 'eMadrasah',
        'app_description' => 'Sistem Informasi Manajemen Madrasah', 'institution_name' => 'Madrasah',
        'app_email' => null, 'app_phone' => null, 'app_website' => null,
        'primary_logo' => null, 'login_logo' => null, 'print_logo' => null, 'favicon' => null,
        'primary_color' => '#047857', 'default_theme' => 'light', 'sidebar_mode' => 'expanded',
        'default_language' => 'id', 'timezone' => 'Asia/Jakarta', 'date_format' => 'DD/MM/YYYY',
        'time_format' => '24', 'first_day_of_week' => 'monday', 'maintenance_mode' => false,
        'maintenance_message' => 'Sistem sedang dalam pemeliharaan. Silakan coba kembali beberapa saat lagi.',
        'pagination_size' => 20,
        'attendance_rfid_enabled' => false,
    ];

    private ?array $resolved = null;

    public function all(): array
    {
        if ($this->resolved !== null) return $this->resolved;
        if (! Schema::hasTable('application_settings')) return $this->resolved = self::DEFAULTS;

        $stored = Cache::rememberForever(self::CACHE_KEY, fn (): array => ApplicationSetting::query()->pluck('value', 'key')->all());

        return $this->resolved = collect(self::DEFAULTS)->mapWithKeys(fn ($default, string $key) => [$key => $this->cast($stored[$key] ?? $default, $default)])->all();
    }

    public function get(string $key, mixed $fallback = null): mixed
    {
        return $this->all()[$key] ?? $fallback;
    }

    public function update(User $actor, array $values, array $files = []): void
    {
        $old = $this->all();
        $newPaths = [];
        foreach ($files as $key => $file) {
            if ($file instanceof UploadedFile) {
                $newPaths[$key] = $file->store('application-settings', 'public');
                $values[$key] = $newPaths[$key];
            }
        }

        try {
            DB::transaction(function () use ($actor, $values, $old): void {
                foreach ($values as $key => $value) {
                    if (! array_key_exists($key, self::DEFAULTS)) continue;
                    ApplicationSetting::query()->updateOrCreate(['key' => $key], [
                        'value' => is_bool($value) ? ($value ? '1' : '0') : $value,
                        'type' => $this->typeFor($key), 'group' => $this->groupFor($key),
                    ]);
                }
                $changes = collect($values)->filter(fn ($value, $key) => ($old[$key] ?? null) !== $value && ! in_array($key, ['primary_logo', 'login_logo', 'print_logo', 'favicon'], true))->map(fn ($value, $key) => ['dari' => $old[$key] ?? null, 'menjadi' => $value])->all();
                activity('application-settings')->causedBy($actor)->withProperties(['changes' => $changes, 'branding_changed' => collect($values)->keys()->intersect(['primary_logo', 'login_logo', 'print_logo', 'favicon'])->isNotEmpty()])->log('Mengubah Pengaturan Aplikasi.');
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete(array_values($newPaths));
            throw $exception;
        }

        foreach ($newPaths as $key => $path) {
            $oldPath = $old[$key] ?? null;
            if (is_string($oldPath) && str_starts_with($oldPath, 'application-settings/') && $oldPath !== $path) Storage::disk('public')->delete($oldPath);
        }
        $this->clearCache();
    }

    public function clearCache(): void { $this->resolved = null; Cache::forget(self::CACHE_KEY); }
    public function assetUrl(string $key, ?string $fallback = null): ?string { $path = $this->get($key) ?: $fallback; return $path ? asset('storage/'.$path) : null; }
    private function cast(mixed $value, mixed $default): mixed { return is_bool($default) ? filter_var($value, FILTER_VALIDATE_BOOL) : (is_int($default) ? (int) $value : $value); }
    private function typeFor(string $key): string { return match ($key) { 'maintenance_mode', 'attendance_rfid_enabled' => 'boolean', 'pagination_size' => 'integer', 'primary_logo', 'login_logo', 'print_logo', 'favicon' => 'file', default => 'string' }; }
    private function groupFor(string $key): string { return match ($key) { 'attendance_rfid_enabled' => 'attendance', 'primary_logo', 'login_logo', 'print_logo', 'favicon' => 'branding', 'primary_color', 'default_theme', 'sidebar_mode' => 'appearance', 'default_language', 'timezone', 'date_format', 'time_format', 'first_day_of_week' => 'localization', 'maintenance_mode', 'maintenance_message', 'pagination_size' => 'system', default => 'general' }; }
}
