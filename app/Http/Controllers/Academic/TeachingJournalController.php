<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\{TeachingJournalRequest, TeachingJournalTemplateRequest};
use App\Models\{AcademicSubject, AcademicYear, Classroom, Personnel, Semester, TeachingJournal, TeachingJournalTemplate};
use App\Services\Academic\{TeachingJournalReportService, TeachingJournalService, TeachingJournalTemplateService};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\{RedirectResponse, Request, Response};
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TeachingJournalController extends Controller
{
    public function index(Request $request): View
    {
        [$year, $semester, $date] = $this->filters($request);
        $journals = $this->journalQuery($request, $year, $semester, $date)->latest('journal_date')->paginate(20)->withQueryString();
        return view('academic.teaching-journals.index', $this->options($year) + compact('journals', 'year', 'semester', 'date') + ['template' => TeachingJournalTemplate::where('is_active', true)->latest()->first()]);
    }

    public function create(Request $request): View
    {
        $this->personnelOrAbort($request);
        $year = AcademicYear::where('is_active', true)->value('id');
        $journal = new TeachingJournal(['academic_year_id' => $year, 'semester_id' => Semester::where('academic_year_id', $year)->where('is_active', true)->value('id'), 'journal_date' => today()]);
        return view('academic.teaching-journals.form', $this->options($year) + compact('journal') + ['savedAttendance' => collect()]);
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

    public function edit(Request $request, TeachingJournal $teachingJournal): View
    {
        $this->ensureManage($request, $teachingJournal);
        $teachingJournal->load('attendances');
        return view('academic.teaching-journals.form', $this->options($teachingJournal->academic_year_id) + ['journal' => $teachingJournal, 'savedAttendance' => $teachingJournal->attendances->keyBy('student_id')]);
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

    public function report(Request $request, string $format, TeachingJournalReportService $reports, TeachingJournalTemplateService $templates): Response|BinaryFileResponse
    {
        abort_unless(in_array($format, ['docx', 'pdf'], true), 404);
        [$year, $semester, $date] = $this->filters($request);
        $journals = $this->journalQuery($request, $year, $semester, $date)->oldest('journal_date')->get();
        abort_if($journals->isEmpty(), 422, 'Tidak ada jurnal pada filter laporan.');
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

    private function options(int $year): array
    {
        return ['years' => AcademicYear::latest('starts_at')->get(), 'semesters' => Semester::where('academic_year_id', $year)->get(), 'rooms' => Classroom::with(['gradeLevel', 'students'])->where('academic_year_id', $year)->where('is_active', true)->get(), 'subjects' => AcademicSubject::where('is_active', true)->orderBy('sort_order')->get(), 'teachers' => Personnel::where('is_active', true)->orderBy('full_name')->get()];
    }

    private function personnelOrAbort(Request $request): void { abort_unless($request->user()->personnel?->is_active, 403, TeachingJournalService::NO_PERSONNEL); }
    private function ensureView(Request $request, TeachingJournal $journal): void { abort_if(! $request->user()->can('teaching-journals.view-all') && $journal->personnel_id !== $request->user()->personnel?->id, 403); }
    private function ensureManage(Request $request, TeachingJournal $journal): void { abort_unless($request->user()->can('teaching-journals.manage'), 403); $this->ensureView($request, $journal); }
}
