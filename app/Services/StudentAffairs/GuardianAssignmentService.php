<?php

declare(strict_types=1);

namespace App\Services\StudentAffairs;

use App\Models\Student;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GuardianAssignmentService
{
    public function __construct(private ActivityLogger $logger) {}

    public function attach(Student $student, array $data): void
    {
        DB::transaction(function () use ($student, $data): void {
            if ($student->guardians()->whereKey($data['guardian_id'])->exists()) {
                throw ValidationException::withMessages(['guardian_id' => 'Wali sudah terhubung dengan siswa ini.']);
            }

            if (! empty($data['is_primary'])) {
                $student->guardians()->wherePivot('is_primary', true)->update(['guardian_student.is_primary' => false]);
            }

            $student->guardians()->attach($data['guardian_id'], collect($data)->except('guardian_id')->all());
            $this->logger->log('student.guardian.attached', $student, [], $data, 'Wali siswa ditautkan.');
        });
    }

    public function update(Student $student, int $guardianId, array $data): void
    {
        DB::transaction(function () use ($student, $guardianId, $data): void {
            if (! $student->guardians()->whereKey($guardianId)->exists()) {
                throw ValidationException::withMessages(['guardian_id' => 'Relasi wali tidak ditemukan.']);
            }

            if (! empty($data['is_primary'])) {
                $student->guardians()->wherePivot('is_primary', true)->whereKeyNot($guardianId)->update(['guardian_student.is_primary' => false]);
            }

            $student->guardians()->updateExistingPivot($guardianId, $data);
            $this->logger->log('student.guardian.updated', $student, [], ['guardian_id' => $guardianId] + $data, 'Relasi wali siswa diperbarui.');
        });
    }

    public function detach(Student $student, int $guardianId): void
    {
        DB::transaction(function () use ($student, $guardianId): void {
            $student->guardians()->detach($guardianId);
            $this->logger->log('student.guardian.detached', $student, ['guardian_id' => $guardianId], [], 'Relasi wali siswa dihapus.');
        });
    }
}
