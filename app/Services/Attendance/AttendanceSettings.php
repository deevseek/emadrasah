<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Models\SchoolSetting;

final class AttendanceSettings
{
    public function lateAfter(): string
    {
        $minutes = (int) $this->value('attendance', 'late_tolerance_minutes', '10');

        return now()->setTime(7, 0)->addMinutes($minutes)->format('H:i');
    }

    public function gpsRequired(): bool
    {
        return filter_var($this->value('attendance', 'gps_required', config('attendance.gps_required', false)), FILTER_VALIDATE_BOOL);
    }

    public function selfieRequired(): bool
    {
        return filter_var($this->value('attendance', 'selfie_required', config('attendance.selfie_required', false)), FILTER_VALIDATE_BOOL);
    }

    public function schoolLatitude(): float
    {
        return (float) $this->value('attendance', 'school_latitude', '0');
    }

    public function schoolLongitude(): float
    {
        return (float) $this->value('attendance', 'school_longitude', '0');
    }

    public function radiusMeters(): int
    {
        return (int) $this->value('attendance', 'radius_meters', '100');
    }

    private function value(string $group, string $key, mixed $default): mixed
    {
        return SchoolSetting::query()
            ->where('group', $group)
            ->where('key', $key)
            ->value('value') ?? $default;
    }
}
