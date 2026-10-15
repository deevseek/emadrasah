<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\{TeachingJournalRequest, TeachingJournalTemplateRequest};
use App\Models\{AcademicSubject, AcademicYear, Classroom, Personnel, Semester, TeachingJournal, TeachingJournalTemplate};
use App\Services\Academic\{AcademicAccessService, JournalAttendanceResolver, TeachingJournalReportService, TeachingJournalService, TeachingJournalTemplateService};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\{RedirectResponse, Request, Response};
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TeachingJournalController extends Controller
{
    public function __construct(private readonly AcademicAccessService $access) {}

    public function index(Request $request): View
    {
        [$year, $semester, $date] = $this->filters($request);
        $journals = $this->journalQuery($request, $year, $semester, $date)->latest('journal_date')->paginate(20)->withQueryString();
        return view('academic.teaching-journals.index', $this->options($year) + compact('journals', 'year', 'semester', 'date') + ['template' => TeachingJournalTemplate::where('is_active', true)->latest()->first()]);
    }

    public function create(Request $request, TeachingJournalService $service, JournalAttendanceResolver $attendance): View
    {
        abort_unless($service->activePersonnel($request->user()), 403, TeachingJournalService::NO_PERSONNEL);
        $year = $request->integer('academic_year_id') ?: (int) AcademicYear::where('is_active', true)->value('id');
        if ($request->filled('academic_year_id') && ! AcademicYear::whereKey($year)->exists()) {
            throw ValidationException::withMessages(['academic_year_id' => 'Tahun ajaran yang dipilih tidak ditemukan.']);
        }
        $yearSemesters = Semester::where('academic_year_id', $year)->get();
        $semester = $request->integer('semester_id') ?: (int) ($yearSemesters->firstWhere('is_active', true)?->id ?? $yearSemesters->first()?->id);
        if ($request->filled('semester_id') && ! Semester::whereKey($semester)->where('academic_year_id', $year)->exists()) {
            throw ValidationException::withMessages(['semester_id' => 'Semester tidak sesuai dengan tahun ajaran yang dipilih.']);
        }
        $date = $request->date('journal_date') ?? today();
        $rooms = $this->access->classrooms($request->user())->with('students')->where('academic_year_id', $year)->get();
        $classroomId = $request->filled('classroom_id') ? $request->integer('classroom_id') : (int) $rooms->first()?->id;
        if ($request->filled('classroom_id') && ! $rooms->contains('id', $classroomId)) {
            throw ValidationException::withMessages(['classroom_id' => 'Rombel tidak tersedia atau tidak dapat Anda akses.']);
        }
        $journal = new TeachingJournal(['academic_year_id' => $year, 'semester_id' => $semester, 'classroom_id' => $classroomId ?: null, 'academic_subject_id' => $request->integer('academic_subject_id') ?: null, 'journal_date' => $date, 'lesson_number' => $request->input('lesson_number')]);
        $dailyAttendance = $classroomId > 0 ? $attendance->daily($classroomId, $date) : collect();
        $snapshotAttendance = collect();
        return view('academic.teaching-journals.form', $this->options($year, $rooms, $yearSemesters) + compact('journal', 'dailyAttendance', 'snapshotAttendance'));
    }

    public function store(TeachingJournalRequest $request, TeachingJournalService $service): RedirectResponse
    {
        $journal = $service->save($request->validated(), $request->user());
        return redirect()->route('academic.teaching-journals.show', $journal)->with('success', 'Jurnal mengajar dan absensi siswa berhasil disimpan.');
    }

    public function show(Request $request, TeachingJournal $teachingJournal): View
    {
        $this->ensureView($request, $teachingJournal);
        $teachingJournal->load(['personnel', 'classroom.gradeLevel', 'subject', 'attendances.student']);
        return view('academic.teaching-journals.show', ['journal' => $teachingJournal, 'attendance' => $teachingJournal->attendances->countBy(fn ($row) => $row->status->value)]);
    }

    public function edit(Request $request, TeachingJournal $teachingJournal, JournalAttendanceResolver $attendance): View
    {
        $this->ensureManage($request, $teachingJournal);
        $teachingJournal->load('attendances');
        return view('academic.teaching-journals.form', $this->options($teachingJournal->academic_year_id) + ['journal' => $teachingJournal, 'dailyAttendance' => $attendance->daily($teachingJournal->classroom_id, $teachingJournal->journal_date), 'snapshotAttendance' => $teachingJournal->attendances->keyBy('student_id')]);
    }

    public function update(TeachingJournalRequest $request, TeachingJournal $teachingJournal, TeachingJournalService $service): RedirectResponse
    {
        $journal = $service->save($request->validated(), $request->user(), $teachingJournal);
        return redirect()->route('academic.teaching-journals.show', $journal)->with('success', 'Jurnal mengajar dan absensi siswa berhasil diperbarui.');
    }

    public function destroy(Request $request, TeachingJournal $teachingJournal, TeachingJournalService $service): RedirectResponse
    {
        $service->delete($teachingJournal, $request->user());
        return redirect()->route('academic.teaching-journals.index')->with('success', 'Jurnal mengajar berhasil dihapus.');
    }

    public function uploadTemplate(TeachingJournalTemplateRequest $request, TeachingJournalTemplateService $service): RedirectResponse
    {
        $service->upload($request->string('name')->toString(), $request->file('template'), $request->user());
        return back()->with('success', 'Template Word jurnal berhasil diunggah dan diaktifkan.');
    }

    public function report(Request $request, string $format, TeachingJournalReportService $reports, TeachingJournalTemplateService $templates): RedirectResponse|Response|BinaryFileResponse
    {
        abort_unless(in_array($format, ['docx', 'pdf'], true), 404);
        [$year, $semester, $date] = $this->filters($request);
        $journals = $this->journalQuery($request, $year, $semester, $date)->oldest('journal_date')->get();
        if ($journals->isEmpty()) {
            return redirect()->route('academic.teaching-journals.index', $request->query())
                ->with('error', 'Laporan tidak dapat diunduh karena tidak ada jurnal pada filter yang dipilih.');
        }
        $journals->load(['academicYear', 'semester', 'classroom.gradeLevel', 'subject', 'personnel', 'attendances']);
        $filename = 'laporan-jurnal-'.$date;
        if ($format === 'pdf') return Pdf::loadView('academic.teaching-journals.report', compact('journals'))->setPaper('a4', 'landscape')->download($filename.'.pdf');
        $template = TeachingJournalTemplate::where('is_active', true)->latest()->firstOrFail();
        $path = $reports->createDocx($template, $journals, $templates->absolutePath($template));
        return response()->download($path, $filename.'.docx')->deleteFileAfterSend(true);
    }

    private function journalQuery(Request $request, int $year, int $semester, string $date): Builder
    {
        $query = TeachingJournal::with(['personnel', 'classroom.gradeLevel', 'subject'])->where(['academic_year_id' => $year, 'semester_id' => $semester])->whereDate('journal_date', $date);
        if (! $request->user()->can('teaching-journals.view-all')) $query->where('personnel_id', $request->user()->personnel?->id ?? 0);
        foreach (['classroom_id', 'academic_subject_id', 'personnel_id'] as $field) if ($request->filled($field)) $query->where($field, $request->integer($field));
        return $query;
    }

    private function filters(Request $request): array
    {
        $year = $request->integer('academic_year_id') ?: (int) AcademicYear::where('is_active', true)->value('id');
        $semester = $request->integer('semester_id') ?: (int) Semester::where('academic_year_id', $year)->where('is_active', true)->value('id');
        return [$year, $semester, $request->input('journal_date', today()->toDateString())];
    }

    private function options(int $year, ?Collection $rooms = null, ?Collection $semesters = null): array
    {
        return ['years' => AcademicYear::latest('starts_at')->get(), 'semesters' => $semesters ?? Semester::where('academic_year_id', $year)->get(), 'rooms' => $rooms ?? Classroom::with(['gradeLevel', 'students'])->where('academic_year_id', $year)->where('is_active', true)->get(), 'subjects' => AcademicSubject::where('is_active', true)->orderBy('sort_order')->get(), 'teachers' => Personnel::where('is_active', true)->orderBy('full_name')->get()];
    }

    private function ensureView(Request $request, TeachingJournal $journal): void { abort_if(! $request->user()->can('teaching-journals.view-all') && $journal->personnel_id !== $request->user()->personnel?->id, 403); }
    private function ensureManage(Request $request, TeachingJournal $journal): void { abort_unless($request->user()->can('teaching-journals.manage'), 403); $this->ensureView($request, $journal); }
}
