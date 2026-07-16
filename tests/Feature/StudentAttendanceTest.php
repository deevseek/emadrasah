<?php declare(strict_types=1); use App\Enums\AttendanceStatus; use App\Models\StudentAttendance;
it('defines student bulk attendance status and unique-date model fields', function (): void { expect(AttendanceStatus::Alpha->label())->toBe('Alpha'); expect((new StudentAttendance)->getFillable())->toContain('student_id','classroom_id','academic_year_id','attendance_date','recorded_by'); });
