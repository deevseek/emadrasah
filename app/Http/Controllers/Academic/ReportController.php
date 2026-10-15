<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\{AcademicYear, Semester, StudentAttendance, StudentGrade, TeachingJournal};
use App\Services\Academic\AcademicAccessService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Mendukung route eksplisit `[ReportController::class, 'index']` yang sudah
     * tersimpan pada route cache di sebagian instalasi production.
     */
    public function index(Request $request, AcademicAccessService $access): View
    {
        $year = $request->integer('academic_year_id') ?: AcademicYear::where('is_active', true)->value('id');
        $semester = $request->integer('semester_id') ?: Semester::where('academic_year_id', $year)->where('is_active', true)->value('id');
        $roomId = $request->integer('classroom_id');
        $room = $roomId ? $access->ensureClassroom($request->user(), $roomId) : null;
        $students = $room?->students()->orderBy('full_name')->get() ?? collect();
        $studentId = $request->integer('student_id');

        if ($studentId && ! $students->contains('id', $studentId)) {
            abort(422, 'Siswa tidak terdaftar pada rombel ini.');
        }

        $attendance = StudentAttendance::where(['semester_id' => $semester, 'classroom_id' => $roomId])->get()->groupBy('student_id');
        $grades = StudentGrade::with('subject')->where(['semester_id' => $semester, 'classroom_id' => $roomId])->get()->groupBy('student_id');
        $from = $request->date('from_date')?->toDateString() ?? today()->startOfMonth()->toDateString();
        $to = $request->date('to_date')?->toDateString() ?? today()->toDateString();
        $teachingQuery = TeachingJournal::with(['personnel', 'classroom.gradeLevel', 'subject'])
            ->where('academic_year_id', $year)
            ->where('semester_id', $semester)
            ->whereBetween('journal_date', [$from, $to]);
        if ($roomId) {
            $teachingQuery->where('classroom_id', $roomId);
        }
        $teachingRecap = $teachingQuery->get();
        $teacherRecap = $teachingRecap->groupBy('personnel_id')->map(fn ($rows) => [
            'teacher' => $rows->first()->personnel->full_name,
            'journals' => $rows->count(),
            'classrooms' => $rows->unique('classroom_id')->count(),
            'subjects' => $rows->unique('academic_subject_id')->count(),
            'last_meeting' => $rows->max('journal_date'),
        ])->sortByDesc('journals')->values();
        $classroomRecap = $teachingRecap->groupBy('classroom_id')->map(fn ($rows) => [
            'classroom' => $rows->first()->classroom->display_name,
            'journals' => $rows->count(),
            'teachers' => $rows->unique('personnel_id')->count(),
            'last_meeting' => $rows->max('journal_date'),
        ])->sortByDesc('journals')->values();

        return view('academic.reports.index', compact(
            'year', 'semester', 'room', 'students', 'studentId', 'attendance', 'grades',
            'from', 'to', 'teachingRecap', 'teacherRecap', 'classroomRecap',
        ) + [
            'years' => AcademicYear::orderByDesc('starts_at')->get(),
            'semesters' => Semester::where('academic_year_id', $year)->get(),
            'rooms' => $access->classrooms($request->user())->where('academic_year_id', $year)->get(),
        ]);
    }

    /**
     * Kompatibilitas untuk instalasi yang masih menggunakan controller invokable.
     */
    public function __invoke(Request $request, AcademicAccessService $access): View
    {
        return $this->index($request, $access);
    }
    public function exportAttendance(
        \App\Http\Requests\Academic\ExportAttendanceRequest $request,
        AcademicAccessService $access,
        \App\Services\Academic\StudentAttendanceXlsxService $service,
    ): \Symfony\Component\HttpFoundation\BinaryFileResponse {
        $classroom = $access->ensureClassroom($request->user(), $request->integer('classroom_id'));
        $month = \Carbon\CarbonImmutable::createFromFormat('!Y-m', $request->validated('month'));
        $path = $service->export($classroom, $month, $request->user());

        return response()->download($path, $service->filename($classroom, $month), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

}
