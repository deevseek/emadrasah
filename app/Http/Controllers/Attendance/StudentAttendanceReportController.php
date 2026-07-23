<?php

declare(strict_types=1);

namespace App\Http\Controllers\Attendance;

use App\Enums\EnrollmentStatus;
use App\Enums\Gender;
use App\Enums\StudentAttendanceSessionStatus;
use App\Enums\StudentAttendanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\StudentAttendanceFilterRequest;
use App\Models\Classroom;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Services\Foundation\SchoolProfileService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class StudentAttendanceReportController extends Controller
{
    public function index(StudentAttendanceFilterRequest $request, SchoolProfileService $profiles): View
    {
        return view('attendance.students.report', $this->data($request, $profiles));
    }

    public function print(StudentAttendanceFilterRequest $request, SchoolProfileService $profiles): View
    {
        return view('attendance.students.print', $this->data($request, $profiles));
    }

    public function export(StudentAttendanceFilterRequest $request, SchoolProfileService $profiles): Response
    {
        $data = $this->data($request, $profiles);
        $csv = "No,Nama Siswa,JK,".implode(',', $data['days']).",S,I,A,Keterangan\n";

        foreach ($data['matrixRows'] as $row) {
            $csv .= implode(',', array_map(fn ($value) => '"'.str_replace('"', '""', (string) $value).'"', [
                $row['number'],
                $row['student_name'],
                $row['gender_code'],
                ...array_map(fn ($day) => $row['days'][$day] ?? '', $data['days']),
                $row['summary']['sakit'],
                $row['summary']['izin'],
                $row['summary']['alpha'],
                $row['notes'],
            ]))."\n";
        }

        activity('student-attendances')->causedBy($request->user())->log('Rekap absensi siswa bulanan diekspor');

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="rekap-absensi-siswa-'.$data['month']->format('Y-m').'.csv"',
        ]);
    }

    private function data(StudentAttendanceFilterRequest $request, SchoolProfileService $profiles): array
    {
        $classrooms = Classroom::query()->with('homeroomTeacher')->where('is_active', true)->orderBy('name')->get();
        $classroom = $request->classroom_id ? $classrooms->firstWhere('id', (int) $request->classroom_id) : $classrooms->first();
        $month = CarbonImmutable::createFromFormat('Y-m-d', ($request->input('month') ?: now()->format('Y-m')).'-01')->startOfMonth();
        $days = range(1, 31);
        $matrixRows = $classroom ? $this->monthlyMatrix($classroom, $month) : collect();
        $summary = $matrixRows->reduce(fn (array $carry, array $row) => [
            'sakit' => $carry['sakit'] + $row['summary']['sakit'],
            'izin' => $carry['izin'] + $row['summary']['izin'],
            'alpha' => $carry['alpha'] + $row['summary']['alpha'],
        ], ['sakit' => 0, 'izin' => 0, 'alpha' => 0]);

        return [
            'profile' => $profiles->current(),
            'classrooms' => $classrooms,
            'classroom' => $classroom,
            'month' => $month,
            'days' => $days,
            'daysInMonth' => $month->daysInMonth,
            'matrixRows' => $matrixRows,
            'summary' => $summary,
            'statuses' => StudentAttendanceStatus::options(),
        ];
    }

    private function monthlyMatrix(Classroom $classroom, CarbonImmutable $month): Collection
    {
        $start = $month->startOfMonth()->toDateString();
        $end = $month->endOfMonth()->toDateString();
        $enrollments = StudentEnrollment::query()
            ->with('student')
            ->where('classroom_id', $classroom->id)
            ->where('enrollment_status', EnrollmentStatus::Active->value)
            ->whereDate('enrolled_at', '<=', $end)
            ->where(fn ($query) => $query->whereNull('completed_at')->orWhereDate('completed_at', '>=', $start))
            ->whereHas('student', fn ($query) => $query->where('is_active', true))
            ->join('students', 'students.id', '=', 'student_enrollments.student_id')
            ->orderBy('students.name')
            ->select('student_enrollments.*')
            ->get();

        $attendances = StudentAttendance::query()
            ->with('student')
            ->where('classroom_id', $classroom->id)
            ->whereBetween('attendance_date', [$start, $end])
            ->whereHas('session', fn ($query) => $query->where('status', StudentAttendanceSessionStatus::Final->value))
            ->get()
            ->groupBy('student_enrollment_id');

        return $enrollments->values()->map(function (StudentEnrollment $enrollment, int $index) use ($attendances): array {
            $studentAttendances = $attendances->get($enrollment->id, collect())->keyBy(fn (StudentAttendance $attendance) => (int) $attendance->attendance_date->format('j'));
            $days = [];
            $notes = [];
            $summary = ['sakit' => 0, 'izin' => 0, 'alpha' => 0];

            foreach (range(1, 31) as $day) {
                $attendance = $studentAttendances->get($day);
                $code = $this->statusCode($attendance?->status);
                $days[$day] = $code;

                if ($attendance?->status === StudentAttendanceStatus::Sick) {
                    $summary['sakit']++;
                } elseif ($attendance?->status === StudentAttendanceStatus::Permission) {
                    $summary['izin']++;
                } elseif ($attendance?->status === StudentAttendanceStatus::Alpha) {
                    $summary['alpha']++;
                }

                if ($attendance?->notes) {
                    $notes[] = $day.': '.$attendance->notes;
                }
            }

            return [
                'number' => $index + 1,
                'student_name' => $enrollment->student->name,
                'gender_code' => $enrollment->student->gender === Gender::Male ? 'L' : 'P',
                'days' => $days,
                'summary' => $summary,
                'notes' => implode('; ', $notes),
            ];
        });
    }

    private function statusCode(?StudentAttendanceStatus $status): string
    {
        return match ($status) {
            StudentAttendanceStatus::Sick => 'S',
            StudentAttendanceStatus::Permission => 'I',
            StudentAttendanceStatus::Alpha => 'A',
            StudentAttendanceStatus::Late => 'T',
            StudentAttendanceStatus::EarlyLeave => 'P',
            StudentAttendanceStatus::Duty => 'D',
            StudentAttendanceStatus::Unscheduled => '-',
            default => '',
        };
    }

}
