<?php
declare(strict_types=1);
namespace App\Http\Controllers\Academic;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\ExportAcademicAttendanceReportRequest;
use App\Models\{AcademicYear, Semester, StudentAttendance, StudentGrade, TeachingJournal};
use App\Services\Academic\{AcademicAccessService, StudentAttendanceXlsxService};
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
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
        $teachingRecap = TeachingJournal::with(['personnel', 'classroom', 'subject'])->where('academic_year_id', $year)->whereBetween('journal_date', [$from, $to])->get();

        return view('academic.reports.index', compact('year', 'semester', 'room', 'students', 'studentId', 'attendance', 'grades', 'from', 'to', 'teachingRecap') + [
            'years' => AcademicYear::orderByDesc('starts_at')->get(),
            'semesters' => Semester::where('academic_year_id', $year)->get(),
            'rooms' => $access->classrooms($request->user())->where('academic_year_id', $year)->get(),
            'attendanceMonth' => $request->input('month', now()->format('Y-m')),
        ]);
    }

    public function exportAttendance(ExportAcademicAttendanceReportRequest $request, AcademicAccessService $access, StudentAttendanceXlsxService $service): BinaryFileResponse
    {
        $classroom = $access->ensureClassroom($request->user(), $request->integer('classroom_id'));
        $month = CarbonImmutable::createFromFormat('!Y-m', $request->validated('month'));
        $path = $service->export($classroom, $month, $request->user());

        return response()->download($path, $service->filename($classroom, $month), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }
}
