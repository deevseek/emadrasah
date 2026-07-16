<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Enums\EnrollmentStatus;
use App\Models\Classroom;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Services\ActivityLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class StudentAttendanceService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function bulk(Classroom $classroom, string $date, array $rows): void
    {
        $attendanceDate = CarbonImmutable::parse($date)->toDateString();

        DB::transaction(function () use ($classroom, $attendanceDate, $rows): void {
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

                $attendance = StudentAttendance::query()
                    ->where('student_id', $studentId)
                    ->whereDate('attendance_date', $attendanceDate)
                    ->lockForUpdate()
                    ->first();

                $old = [];
                $attributes = [
                    'classroom_id' => $classroom->id,
                    'academic_year_id' => $activeEnrollments[(int) $studentId],
                    'status' => $row['status'],
                    'notes' => $row['notes'] ?? null,
                    'recorded_by' => auth()->id(),
                ];

                if ($attendance instanceof StudentAttendance) {
                    $old = $attendance->getOriginal();
                    $attendance->update($attributes);
                } else {
                    $attendance = StudentAttendance::query()->create($attributes + [
                        'student_id' => $studentId,
                        'attendance_date' => $attendanceDate,
                    ]);
                }

                $this->logger->log('student-attendance.saved', $attendance, $old, $attendance->fresh()->toArray());
            }
        });
    }
}
