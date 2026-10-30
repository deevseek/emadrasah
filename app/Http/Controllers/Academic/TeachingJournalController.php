<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\TeachingJournalRequest;
use App\Http\Requests\Academic\TeachingJournalTemplateRequest;
use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassroomMembership;
use App\Models\Personnel;
use App\Models\Semester;
use App\Models\TeachingJournal;
use App\Models\TeachingJournalAttachment;
use App\Models\TeachingJournalTemplate;
use App\Services\Academic\JournalAttendanceResolver;
use App\Services\Academic\TeachingJournalAccessService;
use App\Services\Academic\TeachingJournalReportService;
use App\Services\Academic\TeachingJournalService;
use App\Services\Academic\TeachingJournalTemplateService;
use App\Services\Settings\ApplicationSettingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeachingJournalController extends Controller
{
    public function __construct(private readonly TeachingJournalAccessService $access) {}

    public function index(Request $request): View
    {
        [$year, $semester, $date] = $this->filters($request);
        $journals = $this->journalQuery($request, $year, $semester, $date)->latest('journal_date')->paginate(20)->withQueryString();

        return view('academic.teaching-journals.index', $this->options($year) + compact('journals', 'year', 'semester', 'date') + ['template' => TeachingJournalTemplate::where('is_active', true)->latest()->first()]);
    }

    public function create(Request $request, JournalAttendanceResolver $attendance): View
    {
        $this->restoreContext($request);
        $this->access->ensureManage($request->user());
        $year = $request->integer('academic_year_id') ?: (int) AcademicYear::where('is_active', true)->value('id');
        if ($request->filled('academic_year_id') && ! AcademicYear::whereKey($year)->exists()) {
            throw ValidationException::withMessages(['academic_year_id' => 'Tahun ajaran yang dipilih tidak ditemukan.']);
        }
        $date = $request->date('journal_date') ?? today();
        $yearSemesters = Semester::where('academic_year_id', $year)->get();
        $semester = $request->integer('semester_id') ?: $this->semesterForDate($yearSemesters, $date);
        if ($request->filled('semester_id') && ! Semester::whereKey($semester)->where('academic_year_id', $year)->exists()) {
            throw ValidationException::withMessages(['semester_id' => 'Semester tidak sesuai dengan tahun ajaran yang dipilih.']);
        }
        $rooms = $this->access->classrooms($request->user(), $year, $semester)->get();
        $classroomId = $request->filled('classroom_id') ? $request->integer('classroom_id') : (int) $rooms->first()?->id;
        if ($request->filled('classroom_id') && ! $rooms->contains('id', $classroomId)) {
            throw ValidationException::withMessages(['classroom_id' => 'Rombel tidak tersedia atau tidak dapat Anda akses.']);
        }
        $journal = new TeachingJournal(['academic_year_id' => $year, 'semester_id' => $semester, 'classroom_id' => $classroomId ?: null, 'academic_subject_id' => $request->integer('academic_subject_id') ?: null, 'journal_date' => $date, 'lesson_number' => $request->input('lesson_number')]);
        $this->loadRoster($rooms, $date);
        $dailyAttendance = $classroomId > 0 ? $attendance->daily($classroomId, $date) : collect();
        $snapshotAttendance = collect();

        return view('academic.teaching-journals.form', $this->formOptions($request, $journal, $rooms, $yearSemesters) + compact('journal', 'dailyAttendance', 'snapshotAttendance'));
    }

    public function store(TeachingJournalRequest $request, TeachingJournalService $service): RedirectResponse
    {
        $journal = $service->save($request->validated(), $request->user());

        return redirect()->route('academic.teaching-journals.show', $journal)->with('success', 'Jurnal mengajar dan absensi siswa berhasil disimpan.');
    }

    public function show(Request $request, TeachingJournal $teachingJournal): View
    {
        $this->ensureView($request, $teachingJournal);
        $teachingJournal->load(['personnel', 'classroom.gradeLevel', 'subject', 'attendances.student', 'attachments']);

        return view('academic.teaching-journals.show', ['journal' => $teachingJournal, 'attendance' => $teachingJournal->attendances->countBy(fn ($row) => $row->status->value)]);
    }

    public function edit(Request $request, TeachingJournal $teachingJournal, JournalAttendanceResolver $attendance): View
    {
        $this->ensureManage($request, $teachingJournal);
        $this->restoreContext($request);
        $teachingJournal->load(['attendances', 'attachments']);
        $year = $request->integer('academic_year_id') ?: $teachingJournal->academic_year_id;
        $date = $request->date('journal_date') ?? $teachingJournal->journal_date;
        $samePeriod = (int) $year === (int) $teachingJournal->academic_year_id && $date->isSameDay($teachingJournal->journal_date);
        $semester = $request->integer('semester_id') ?: ($samePeriod ? $teachingJournal->semester_id : $this->semesterForDate(Semester::where('academic_year_id', $year)->get(), $date));
        $rooms = $this->access->classrooms($request->user(), $year, $semester)->get();
        if ($date->isSameDay($teachingJournal->journal_date) && $year === $teachingJournal->academic_year_id && $semester === $teachingJournal->semester_id && ! $rooms->contains('id', $teachingJournal->classroom_id)) {
            $rooms->push(Classroom::with('gradeLevel')->findOrFail($teachingJournal->classroom_id));
        }
        $room = $request->integer('classroom_id') ?: ($rooms->contains('id', $teachingJournal->classroom_id) ? $teachingJournal->classroom_id : (int) $rooms->first()?->id);
        if (! $rooms->contains('id', $room)) {
            throw ValidationException::withMessages(['classroom_id' => 'Rombel tidak tersedia atau tidak dapat Anda akses.']);
        }
        $sameContext = $room === $teachingJournal->classroom_id && $date->isSameDay($teachingJournal->journal_date);
        $snapshotAttendance = $sameContext ? $teachingJournal->attendances->keyBy('student_id') : collect();
        $teachingJournal->fill(['academic_year_id' => $year, 'semester_id' => $semester, 'classroom_id' => $room, 'journal_date' => $date]);
        $this->loadRoster($rooms, $date);

        return view('academic.teaching-journals.form', $this->formOptions($request, $teachingJournal, $rooms) + ['journal' => $teachingJournal, 'dailyAttendance' => $attendance->daily($room, $date), 'snapshotAttendance' => $snapshotAttendance]);
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

    public function attachment(Request $request, TeachingJournal $teachingJournal, TeachingJournalAttachment $attachment): StreamedResponse
    {
        $this->ensureView($request, $teachingJournal);
        abort_unless((int) $attachment->teaching_journal_id === (int) $teachingJournal->id && $attachment->path, 404);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name, [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
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
        if ($format === 'pdf') {
            return Pdf::loadView('academic.teaching-journals.report', compact('journals'))->setPaper('a4', 'landscape')->download($filename.'.pdf');
        }
        $template = TeachingJournalTemplate::where('is_active', true)->latest()->firstOrFail();
        $path = $reports->createDocx($template, $journals, $templates->absolutePath($template));

        return response()->download($path, $filename.'.docx')->deleteFileAfterSend(true);
    }

    private function journalQuery(Request $request, int $year, int $semester, string $date): Builder
    {
        $query = TeachingJournal::with(['personnel', 'classroom.gradeLevel', 'subject'])->where(['academic_year_id' => $year, 'semester_id' => $semester])->whereDate('journal_date', $date);
        if (! $request->user()->can('teaching-journals.view-all')) {
            $query->where('personnel_id', app(TeachingJournalService::class)->activePersonnel($request->user())?->id ?? 0);
        }
        foreach (['classroom_id', 'academic_subject_id', 'personnel_id'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->integer($field));
            }
        }

        return $query;
    }

    private function filters(Request $request): array
    {
        $year = $request->integer('academic_year_id') ?: (int) AcademicYear::where('is_active', true)->value('id');
        $semester = $request->integer('semester_id') ?: (int) Semester::where('academic_year_id', $year)->where('is_active', true)->value('id');

        return [$year, $semester, $request->input('journal_date', today()->toDateString())];
    }

    private function semesterForDate(Collection $semesters, \Carbon\CarbonInterface $date): int
    {
        $matching = $semesters->first(fn (Semester $semester) => $date->toDateString() >= $semester->starts_at->toDateString()
            && $date->toDateString() <= $semester->ends_at->toDateString());

        return (int) ($matching?->id ?? $semesters->firstWhere('is_active', true)?->id ?? $semesters->first()?->id);
    }

    private function restoreContext(Request $request): void
    {
        if ($request->header('X-Journal-Context')) {
            return;
        }
        foreach (['academic_year_id', 'semester_id', 'classroom_id', 'journal_date', 'lesson_number', 'academic_subject_id'] as $field) {
            if (! $request->filled($field) && $request->session()->hasOldInput($field)) {
                $request->merge([$field => $request->old($field)]);
            }
        }
    }

    private function loadRoster(Collection $rooms, $date): void
    {
        // Muat siswa seluruh rombel sekaligus sesuai tanggal keanggotaan.
        $memberships = ClassroomMembership::whereIn('classroom_id', $rooms->pluck('id'))
            ->whereDate('joined_at', '<=', $date)
            ->where(fn ($q) => $q->whereNull('left_at')->orWhereDate('left_at', '>=', $date))
            ->with('student')->get()->groupBy('classroom_id');
        foreach ($rooms as $room) {
            $room->setRelation('students', $memberships->get($room->id, collect())->pluck('student')->filter()->unique('id'));
        }
    }

    private function formOptions(Request $request, TeachingJournal $journal, Collection $rooms, ?Collection $semesters = null): array
    {
        $subjects = $this->access->subjects($request->user(), (int) $journal->classroom_id, $journal->academic_year_id, $journal->semester_id)->get();
        if ($journal->exists && (int) $journal->classroom_id === (int) $journal->getRawOriginal('classroom_id') && (int) $journal->semester_id === (int) $journal->getRawOriginal('semester_id') && ! $subjects->contains('id', $journal->academic_subject_id)) {
            $subject = AcademicSubject::find($journal->academic_subject_id);
            if ($subject) {
                $subjects->push($subject);
            }
        }

        $settings = app(ApplicationSettingService::class);
        $drivePicker = [
            'enabled' => (bool) $settings->get('google_drive_picker_enabled'),
            'clientId' => $settings->get('google_drive_client_id'),
            'apiKey' => $settings->get('google_drive_api_key'),
            'appId' => $settings->get('google_drive_app_id'),
        ];
        $drivePicker['ready'] = $drivePicker['enabled'] && filled($drivePicker['clientId']) && filled($drivePicker['apiKey']) && filled($drivePicker['appId']);

        return array_replace($this->options($journal->academic_year_id, $rooms, $semesters), ['subjects' => $subjects, 'drivePicker' => $drivePicker]);
    }

    private function options(int $year, ?Collection $rooms = null, ?Collection $semesters = null): array
    {
        return ['years' => AcademicYear::latest('starts_at')->get(), 'semesters' => $semesters ?? Semester::where('academic_year_id', $year)->get(), 'rooms' => $rooms ?? Classroom::with(['gradeLevel', 'students'])->where('academic_year_id', $year)->where('is_active', true)->get(), 'subjects' => AcademicSubject::where('is_active', true)->orderBy('sort_order')->get(), 'teachers' => Personnel::where('is_active', true)->orderBy('full_name')->get()];
    }

    private function ensureView(Request $request, TeachingJournal $journal): void
    {
        $user = $request->user();
        abort_unless($user?->can('teaching-journals.view'), 403);

        if ($user->can('teaching-journals.view-all')) {
            return;
        }

        $personnelId = app(TeachingJournalService::class)->activePersonnel($user)?->id;
        abort_unless(
            $personnelId !== null && (int) $journal->personnel_id === (int) $personnelId,
            403,
            'Anda hanya dapat melihat jurnal milik sendiri.',
        );
    }

    private function ensureManage(Request $request, TeachingJournal $journal): void
    {
        $this->access->ensureManage($request->user(), $journal);
        $this->ensureView($request, $journal);
    }
}
