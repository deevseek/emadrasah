<?php

declare(strict_types=1);

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\Classroom;
use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeLeaveRequest;
use App\Models\StudentAttendance;
use App\Models\TeachingJournal;
use App\Models\Guardian;
use App\Models\GradeLevel;
use App\Models\LoginHistory;
use App\Models\SchoolProfile;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        $classrooms = Classroom::with(['gradeLevel', 'studentEnrollments' => fn ($q) => $q->where('enrollment_status', 'active')])->where('is_active', true)->get();

        return view('dashboard', [
            'title' => 'Dashboard Operasional',
            'profile' => SchoolProfile::first(),
            'activeYear' => $activeYear,
            'activeSemester' => Semester::where('is_active', true)->first(),
            'stats' => [
                'Siswa aktif' => Student::where('is_active', true)->count(),
                'Guru/pegawai aktif' => Employee::where('is_active', true)->count(),
                'Kelas aktif' => $classrooms->count(),
                'Wali aktif' => Guardian::where('is_active', true)->count(),
                'Pengguna aktif' => User::where('is_active', true)->count(),
                'Guru hadir hari ini' => EmployeeAttendance::whereDate('attendance_date', today())->where('status', AttendanceStatus::Present->value)->count(),
                'Guru terlambat' => EmployeeAttendance::whereDate('attendance_date', today())->where('status', AttendanceStatus::Late->value)->count(),
                'Guru izin/sakit' => EmployeeAttendance::whereDate('attendance_date', today())->whereIn('status', [AttendanceStatus::Leave->value, AttendanceStatus::Sick->value])->count(),
                'Siswa hadir' => StudentAttendance::whereDate('attendance_date', today())->where('status', AttendanceStatus::Present->value)->count(),
                'Siswa izin/sakit/alpha' => StudentAttendance::whereDate('attendance_date', today())->whereIn('status', [AttendanceStatus::Leave->value, AttendanceStatus::Sick->value, AttendanceStatus::Alpha->value])->count(),
                'Jurnal hari ini' => TeachingJournal::whereDate('journal_date', today())->count(),
                'Izin pending' => EmployeeLeaveRequest::where('status', LeaveStatus::Pending->value)->count(),
            ],
            'latestLogins' => LoginHistory::where('successful', true)->latest('attempted_at')->limit(5)->get(),
            'latestActivities' => ActivityLog::latest()->limit(5)->get(),
            'latestStudents' => Student::latest()->limit(5)->get(),
            'latestEnrollments' => StudentEnrollment::with(['student', 'classroom', 'academicYear'])->latest()->limit(5)->get(),
            'studentsByGrade' => GradeLevel::withCount(['classrooms as active_students_count' => fn ($q) => $q->join('student_enrollments', 'classrooms.id', '=', 'student_enrollments.classroom_id')->where('student_enrollments.enrollment_status', 'active')])->orderBy('level')->get(),
            'classrooms' => $classrooms,
        ]);
    }
}
