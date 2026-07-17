<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Enums\EnrollmentStatus;
use App\Enums\StudentAttendanceSessionStatus;
use App\Enums\StudentAttendanceStatus;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Semester;
use App\Models\StudentAttendanceSession;
use App\Models\StudentEnrollment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentAttendanceService
{
    public function activeYear(): ?AcademicYear { return AcademicYear::query()->where('is_active', true)->first(); }
    public function activeSemester(): ?Semester { return Semester::query()->where('is_active', true)->first(); }

    public function classroomsFor(User $user): Collection
    {
        $query = Classroom::query()->with('homeroomTeacher')->where('is_active', true)->orderBy('name');
        if ($user->can('student-attendances.view') && ! $user->can('student-attendances.view-own-class')) { return $query->get(); }
        if ($user->can('student-attendances.view') && $user->can('student-attendances.view-own-class')) { return $query->get(); }
        return $query->where('homeroom_teacher_id', $user->employee?->id ?? 0)->get();
    }

    public function canAccessClassroom(User $user, Classroom $classroom): bool
    {
        return $user->can('student-attendances.view') || (($user->can('student-attendances.view-own-class') || $user->can('student-attendances.create')) && (int) $classroom->homeroom_teacher_id === (int) ($user->employee?->id ?? 0));
    }

    public function eligibleEnrollments(Classroom $classroom, CarbonInterface|string $date): Collection
    {
        $d = is_string($date) ? $date : $date->toDateString();
        return StudentEnrollment::query()->with('student')->where('classroom_id', $classroom->id)
            ->where('enrollment_status', EnrollmentStatus::Active->value)
            ->whereDate('enrolled_at', '<=', $d)
            ->where(fn ($q) => $q->whereNull('completed_at')->orWhereDate('completed_at', '>=', $d))
            ->whereHas('student', fn ($q) => $q->where('is_active', true))
            ->get();
    }

    public function findOrCreateSession(Classroom $classroom, string $date, User $user): StudentAttendanceSession
    {
        $year = $this->activeYear() ?? $classroom->academicYear;
        $semester = $this->activeSemester();
        return DB::transaction(function () use ($classroom, $date, $user, $year, $semester) {
            $session = StudentAttendanceSession::query()->where('classroom_id', $classroom->id)->whereDate('attendance_date', $date)->lockForUpdate()->first();
            if ($session) { return $session; }
            $session = StudentAttendanceSession::query()->create(['classroom_id'=>$classroom->id,'academic_year_id'=>$year?->id ?? $classroom->academic_year_id,'semester_id'=>$semester?->id,'attendance_date'=>$date,'status'=>StudentAttendanceSessionStatus::Draft,'recorded_by'=>$user->id]);
            activity('student-attendances')->causedBy($user)->performedOn($session)->withProperties(['kelas'=>$classroom->name,'tanggal'=>$date])->log('Sesi absensi siswa dibuat');
            return $session;
        });
    }

    public function summary(StudentAttendanceSession $session): array
    {
        $counts = $session->attendances()->select('status', DB::raw('count(*) total'))->groupBy('status')->pluck('total', 'status');
        return collect(StudentAttendanceStatus::cases())->mapWithKeys(fn($s)=>[$s->value => (int)($counts[$s->value] ?? 0)])->all();
    }

    public function missingClasses(string $date): Collection
    {
        if ($date > today()->toDateString() || in_array(now()->parse($date)->dayOfWeekIso, [6,7], true)) { return new Collection(); }
        $year = $this->activeYear();
        return Classroom::query()->with('homeroomTeacher')->withCount(['activeStudentEnrollments as students_count'])->where('is_active', true)
            ->when($year, fn($q)=>$q->where('academic_year_id', $year->id))->whereHas('activeStudentEnrollments')
            ->whereDoesntHave('studentAttendanceSessions', fn($q)=>$q->whereDate('attendance_date', $date)->where('status', StudentAttendanceSessionStatus::Final->value))->get();
    }

    public function replaceAttachment($file, ?string $oldPath = null): ?string
    {
        if (! $file) { return $oldPath; }
        $path = $file->store('student-attendance-attachments', 'local');
        if ($oldPath) { Storage::disk('local')->delete($oldPath); }
        return $path;
    }
}
