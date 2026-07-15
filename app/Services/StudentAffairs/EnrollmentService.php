<?php

declare(strict_types=1);

namespace App\Services\StudentAffairs;

use App\Enums\EnrollmentStatus;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnrollmentService
{
    public function __construct(private ActivityLogger $logger) {}

    public function enroll(array $data): StudentEnrollment
    {
        return DB::transaction(function () use ($data): StudentEnrollment {
            $student = Student::query()->lockForUpdate()->findOrFail($data['student_id']);
            $classroom = Classroom::query()->lockForUpdate()->findOrFail($data['classroom_id']);

            $this->validateEnrollment($student, $classroom, (int) $data['academic_year_id']);

            $enrollment = StudentEnrollment::create($data + [
                'enrollment_status' => EnrollmentStatus::Active,
                'enrolled_at' => $data['enrolled_at'] ?? now()->toDateString(),
            ]);

            $this->logger->log('student.enrolled', $enrollment, [], $enrollment->getAttributes(), 'Siswa ditempatkan ke kelas.');

            return $enrollment;
        });
    }

    public function transfer(StudentEnrollment $current, int $classroomId, ?string $notes = null): StudentEnrollment
    {
        return DB::transaction(function () use ($current, $classroomId, $notes): StudentEnrollment {
            $current = StudentEnrollment::query()->lockForUpdate()->findOrFail($current->id);
            $current->update([
                'enrollment_status' => EnrollmentStatus::Transferred,
                'completed_at' => now()->toDateString(),
                'notes' => $notes,
            ]);

            $new = $this->enroll([
                'student_id' => $current->student_id,
                'academic_year_id' => $current->academic_year_id,
                'classroom_id' => $classroomId,
                'notes' => $notes,
            ]);

            $this->logger->log('student.enrollment.transferred', $new, $current->getOriginal(), $new->getAttributes(), 'Siswa dipindahkan kelas.');

            return $new;
        });
    }

    public function withdraw(StudentEnrollment $enrollment, ?string $notes = null): void
    {
        DB::transaction(function () use ($enrollment, $notes): void {
            $old = $enrollment->getOriginal();
            $enrollment->update([
                'enrollment_status' => EnrollmentStatus::Withdrawn,
                'completed_at' => now()->toDateString(),
                'notes' => $notes,
            ]);

            $this->logger->log('student.enrollment.withdrawn', $enrollment, $old, $enrollment->getAttributes(), 'Siswa dikeluarkan dari kelas aktif.');
        });
    }

    private function validateEnrollment(Student $student, Classroom $classroom, int $academicYearId): void
    {
        if ($student->student_status?->blocksActiveEnrollment()) {
            throw ValidationException::withMessages(['student_id' => 'Siswa dengan status nonaktif tidak dapat ditempatkan aktif.']);
        }

        if ((int) $classroom->academic_year_id !== $academicYearId) {
            throw ValidationException::withMessages(['classroom_id' => 'Kelas harus sesuai tahun ajaran.']);
        }

        if (StudentEnrollment::where('student_id', $student->id)->where('academic_year_id', $academicYearId)->where('enrollment_status', EnrollmentStatus::Active)->exists()) {
            throw ValidationException::withMessages(['student_id' => 'Siswa sudah memiliki penempatan aktif pada tahun ajaran ini.']);
        }

        $activeCount = StudentEnrollment::where('classroom_id', $classroom->id)->where('enrollment_status', EnrollmentStatus::Active)->lockForUpdate()->count();
        if ($classroom->capacity !== null && $activeCount >= $classroom->capacity) {
            throw ValidationException::withMessages(['classroom_id' => 'Kapasitas kelas sudah penuh.']);
        }
    }
}
