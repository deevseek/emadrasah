<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Enums\EnrollmentStatus;
use App\Models\Classroom;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class StudentAttendanceService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function bulk(Classroom $classroom, string $date, array $rows): void
    {
        DB::transaction(function () use ($classroom, $date, $rows): void {
            $activeEnrollments = StudentEnrollment::query()
                ->where('classroom_id', $classroom->id)
                ->where('enrollment_status', EnrollmentStatus::Active->value)
                ->pluck('academic_year_id', 'student_id');

            if ($activeEnrollments->isEmpty()) {
                throw ValidationException::withMessages(['classroom_id' => 'Kelas belum memiliki siswa aktif.']);
            }

            foreach ($rows as $studentId => $row) {
                if (! $activeEnrollments->has((int) $studentId)) {
                    throw ValidationException::withMessages(['students' => 'Siswa dari kelas lain tidak boleh dimasukkan.']);
                }

                $attendance = StudentAttendance::query()->updateOrCreate(
                    ['student_id' => $studentId, 'attendance_date' => $date],
                    [
                        'classroom_id' => $classroom->id,
                        'academic_year_id' => $activeEnrollments[(int) $studentId],
                        'status' => $row['status'],
                        'notes' => $row['notes'] ?? null,
                        'recorded_by' => auth()->id(),
                    ]
                );

                $this->logger->log('student-attendance.saved', $attendance, [], $attendance->toArray());
            }
        });
    }
}
