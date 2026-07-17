<?php

declare(strict_types=1);

namespace App\Services\Foundation;

use App\Enums\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\LessonSchedule;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class DashboardMetricsService
{
    public function summary(SchoolProfileService $profiles): array
    {
        $profile = $profiles->current();
        $activeYear = AcademicYear::query()->where('is_active', true)->first();
        $activeSemester = Semester::query()->with('academicYear')->where('is_active', true)->first();
        $activeYearId = $activeYear?->id;
        $activeSemesterId = $activeSemester?->id;

        $classesAtCapacity = 0;
        if ($activeYearId !== null) {
            $classesAtCapacity = Classroom::query()
                ->where('academic_year_id', $activeYearId)
                ->where('is_active', true)
                ->whereNotNull('capacity')
                ->where('capacity', '>', 0)
                ->whereHas('activeStudentEnrollments')
                ->withCount('activeStudentEnrollments')
                ->get()
                ->filter(fn (Classroom $classroom): bool => (int) $classroom->active_student_enrollments_count >= (int) $classroom->capacity)
                ->count();
        }

        return [
            'profile' => $profile,
            'activeYear' => $activeYear,
            'activeSemester' => $activeSemester,
            'activeUsers' => User::query()->where('is_active', true)->count(),
            'inactiveUsers' => User::query()->where('is_active', false)->count(),
            'profileComplete' => $profiles->isComplete($profile),
            'latestActivities' => Activity::query()->where('log_name', 'foundation')->latest()->limit(5)->get(),
            'activeEmployees' => Employee::query()->where('is_active', true)->count(),
            'activeStudents' => Student::query()->where('is_active', true)->count(),
            'activeClassrooms' => Classroom::query()->when($activeYearId, fn ($query) => $query->where('academic_year_id', $activeYearId))->where('is_active', true)->count(),
            'classroomsWithoutHomeroom' => Classroom::query()->when($activeYearId, fn ($query) => $query->where('academic_year_id', $activeYearId))->where('is_active', true)->whereNull('homeroom_teacher_id')->count(),
            'activeStudentsWithoutPlacement' => Student::query()->where('is_active', true)->when($activeYearId, fn ($query) => $query->whereDoesntHave('enrollments', fn ($enrollment) => $enrollment->where('academic_year_id', $activeYearId)->where('enrollment_status', EnrollmentStatus::Active->value)))->count(),
            'classroomsAtOrOverCapacity' => $classesAtCapacity,
            'activeSubjects' => Subject::query()->where('is_active', true)->count(),
            'activeTeachingAssignments' => TeachingAssignment::query()->when($activeYearId, fn ($query) => $query->where('academic_year_id', $activeYearId))->when($activeSemesterId, fn ($query) => $query->where('semester_id', $activeSemesterId))->where('is_active', true)->count(),
            'assignmentsWithoutSchedule' => TeachingAssignment::query()->when($activeYearId, fn ($query) => $query->where('academic_year_id', $activeYearId))->when($activeSemesterId, fn ($query) => $query->where('semester_id', $activeSemesterId))->where('is_active', true)->whereDoesntHave('schedules', fn ($schedule) => $schedule->where('is_active', true))->count(),
            'activeSchedules' => LessonSchedule::query()->when($activeYearId, fn ($query) => $query->where('academic_year_id', $activeYearId))->when($activeSemesterId, fn ($query) => $query->where('semester_id', $activeSemesterId))->where('is_active', true)->count(),
            'subjectsWithoutTeacher' => Subject::query()->where('is_active', true)->whereNotExists(function ($query) use ($activeYearId, $activeSemesterId): void {
                $query->select(DB::raw(1))->from('teaching_assignments')->whereColumn('teaching_assignments.subject_id', 'subjects.id')->where('teaching_assignments.is_active', true);
                if ($activeYearId !== null) { $query->where('teaching_assignments.academic_year_id', $activeYearId); }
                if ($activeSemesterId !== null) { $query->where('teaching_assignments.semester_id', $activeSemesterId); }
            })->count(),
        ];
    }
}
