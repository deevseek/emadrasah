<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Enums\AttendanceStatus;
use App\Enums\WorkScheduleType;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Services\ActivityLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class EmployeeAttendanceService
{
    public function __construct(
        private readonly AttendanceSettings $settings,
        private readonly ActivityLogger $logger,
    ) {}

    public function checkIn(?Employee $employee, array $data, ?UploadedFile $selfie = null): EmployeeAttendance
    {
        $this->ensureEmployeeCanAttend($employee);
        $this->validateGps($data);

        if ($this->settings->selfieRequired() && $selfie === null) {
            throw ValidationException::withMessages(['selfie' => 'Selfie wajib diunggah.']);
        }

        $path = null;

        try {
            if ($selfie !== null) {
                $path = $selfie->store('employee-attendances', 'public');
            }

            return DB::transaction(function () use ($employee, $data, $path): EmployeeAttendance {
                $now = now();
                $date = $now->toDateString();

                if (EmployeeAttendance::query()->where('employee_id', $employee->id)->whereDate('attendance_date', $date)->exists()) {
                    throw ValidationException::withMessages(['attendance' => 'Check-in hanya dapat dilakukan satu kali per tanggal.']);
                }

                $attendance = EmployeeAttendance::query()->create([
                    'employee_id' => $employee->id,
                    'attendance_date' => $date,
                    'checked_in_at' => $now,
                    'status' => $now->format('H:i') > $this->settings->lateAfter() ? AttendanceStatus::Late : AttendanceStatus::Present,
                    'work_schedule_type' => $this->workScheduleTypeFor($employee),
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'accuracy' => $data['accuracy'] ?? null,
                    'location_text' => $data['location_text'] ?? null,
                    'selfie_path' => $path,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => auth()->id(),
                ]);

                $this->logger->log('employee-attendance.checked-in', $attendance, [], $attendance->toArray());

                return $attendance;
            });
        } catch (\Throwable $throwable) {
            if ($path !== null) {
                Storage::disk('public')->delete($path);
            }

            throw $throwable;
        }
    }

    public function checkOut(?Employee $employee): EmployeeAttendance
    {
        $this->ensureEmployeeCanAttend($employee);

        $attendance = $employee->attendances()
            ->whereDate('attendance_date', now()->toDateString())
            ->first();

        if ($attendance === null) {
            throw ValidationException::withMessages(['attendance' => 'Check-out hanya dapat dilakukan setelah check-in.']);
        }

        return DB::transaction(function () use ($attendance): EmployeeAttendance {
            if ($attendance->checked_out_at !== null) {
                throw ValidationException::withMessages(['attendance' => 'Check-out kedua ditolak.']);
            }

            $old = $attendance->toArray();
            $attendance->update(['checked_out_at' => now()]);
            $this->logger->log('employee-attendance.checked-out', $attendance, $old, $attendance->fresh()->toArray());

            return $attendance;
        });
    }

    public function verify(EmployeeAttendance $attendance, array $data): EmployeeAttendance
    {
        return DB::transaction(function () use ($attendance, $data): EmployeeAttendance {
            $old = $attendance->toArray();
            $attendance->update($data + [
                'is_verified' => true,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);
            $this->logger->log('employee-attendance.verified', $attendance, $old, $attendance->fresh()->toArray(), $data['correction_reason']);

            return $attendance;
        });
    }

    private function ensureEmployeeCanAttend(?Employee $employee): void
    {
        if ($employee === null) {
            abort(403, 'Akun pengguna belum terhubung dengan data pegawai.');
        }

        if (! $employee->is_active) {
            abort(403, 'Pegawai nonaktif tidak dapat menggunakan absensi.');
        }
    }

    private function validateGps(array $data): void
    {
        if (! $this->settings->gpsRequired()) {
            return;
        }

        if (! isset($data['latitude'], $data['longitude'], $data['accuracy'])) {
            throw ValidationException::withMessages(['latitude' => 'GPS wajib diambil sebelum check-in.']);
        }

        if ($this->distance((float) $data['latitude'], (float) $data['longitude']) > $this->settings->radiusMeters()) {
            throw ValidationException::withMessages(['latitude' => 'Lokasi berada di luar radius madrasah.']);
        }
    }

    private function distance(float $latitude, float $longitude): float
    {
        $earth = 6371000;
        $latFrom = deg2rad($this->settings->schoolLatitude());
        $lonFrom = deg2rad($this->settings->schoolLongitude());
        $latTo = deg2rad($latitude);
        $lonTo = deg2rad($longitude);
        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;
        $angle = 2 * asin(sqrt((sin($latDelta / 2) ** 2) + cos($latFrom) * cos($latTo) * (sin($lonDelta / 2) ** 2)));

        return $earth * $angle;
    }

    private function workScheduleTypeFor(Employee $employee): WorkScheduleType
    {
        return $employee->employment_type?->value === 'full_day' ? WorkScheduleType::FullDay : WorkScheduleType::Regular;
    }
}
