<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academic;

use App\Enums\DayOfWeek;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\LessonScheduleRequest;
use App\Http\Requests\Academic\LessonScheduleTemplateUploadRequest;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\LessonSchedule;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Services\Academic\LessonScheduleTemplateService;
use App\Services\Academic\ScheduleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScheduleController extends Controller
{
    public function index(Request $request, LessonScheduleTemplateService $templates): View
    {
        $employeeId = $this->viewOwnEmployeeId($request);
        $query = $this->authorizedQuery($request, LessonSchedule::query())
            ->with(['academicYear', 'semester', 'employee', 'classroom', 'subject', 'teachingAssignment'])
            ->when($request->academic_year_id, fn (Builder $query, mixed $value) => $query->where('academic_year_id', $value))
            ->when($request->semester_id, fn (Builder $query, mixed $value) => $query->where('semester_id', $value))
            ->when($request->classroom_id, fn (Builder $query, mixed $value) => $query->where('classroom_id', $value))
            ->when($employeeId ?? $request->employee_id, fn (Builder $query, mixed $value) => $query->where('employee_id', $value))
            ->when($request->subject_id, fn (Builder $query, mixed $value) => $query->where('subject_id', $value))
            ->when($request->day_of_week, fn (Builder $query, mixed $value) => $query->where('day_of_week', $value))
            ->when($request->entry_type, fn (Builder $query, mixed $value) => $query->where('entry_type', $value));
        $weekly = (clone $query)->orderBy('day_of_week')->orderBy('starts_at')->get()->groupBy(fn (LessonSchedule $schedule) => $schedule->day_of_week->value);

        return view('schedules.index', $this->refs($employeeId) + [
            'schedules' => $query->orderBy('day_of_week')->orderBy('starts_at')->paginate(15)->withQueryString(),
            'weekly' => $weekly,
            'timeSlots' => $weekly->flatten()->map(fn (LessonSchedule $schedule) => substr((string) $schedule->starts_at, 0, 5).'–'.substr((string) $schedule->ends_at, 0, 5))->unique()->sort()->values(),
            'filters' => $request->all(),
            'scheduleTemplatePath' => $templates->templatePath(),
            'scheduleTemplateSetting' => $templates->settingRecord(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('schedules.form', $this->refs() + ['schedule' => new LessonSchedule, 'selectedClassroom' => $request->classroom_id]);
    }

    public function store(LessonScheduleRequest $request, ScheduleService $service): RedirectResponse
    {
        $schedule = $service->save($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        return redirect()->route('schedules.show', $schedule)->with('status', 'Jadwal Pelajaran berhasil disimpan.');
    }

    public function show(Request $request, LessonSchedule $schedule): View
    {
        $this->abortUnlessCanView($request, $schedule->employee_id);

        return view('schedules.show', ['schedule' => $schedule->load(['academicYear', 'semester', 'employee', 'classroom', 'subject', 'teachingAssignment'])]);
    }

    public function edit(LessonSchedule $schedule): View
    {
        return view('schedules.form', $this->refs() + ['schedule' => $schedule]);
    }

    public function update(LessonScheduleRequest $request, LessonSchedule $schedule, ScheduleService $service): RedirectResponse
    {
        $service->save($request->validated() + ['is_active' => $request->boolean('is_active')], $schedule);

        return redirect()->route('schedules.show', $schedule)->with('status', 'Jadwal Pelajaran diperbarui.');
    }

    public function toggle(LessonSchedule $schedule, ScheduleService $service): RedirectResponse
    {
        $service->toggle($schedule, ! $schedule->is_active);

        return back()->with('status', 'Status jadwal diperbarui.');
    }

    public function destroy(LessonSchedule $schedule, ScheduleService $service): RedirectResponse
    {
        $service->toggle($schedule, false);

        return back()->with('status', 'Jadwal dinonaktifkan, bukan dihapus.');
    }

    public function print(Request $request): View
    {
        $employeeId = $this->viewOwnEmployeeId($request);

        return view('schedules.print', $this->refs($employeeId) + [
            'items' => $this->authorizedQuery($request, LessonSchedule::query())
                ->with(['employee', 'classroom', 'subject', 'academicYear', 'semester'])
                ->when($request->academic_year_id, fn (Builder $query, mixed $value) => $query->where('academic_year_id', $value))
                ->when($request->semester_id, fn (Builder $query, mixed $value) => $query->where('semester_id', $value))
                ->when($request->classroom_id, fn (Builder $query, mixed $value) => $query->where('classroom_id', $value))
                ->when($employeeId ?? $request->employee_id, fn (Builder $query, mixed $value) => $query->where('employee_id', $value))
                ->when($request->day_of_week, fn (Builder $query, mixed $value) => $query->where('day_of_week', $value))
                ->when($request->entry_type, fn (Builder $query, mixed $value) => $query->where('entry_type', $value))
                ->orderBy('day_of_week')->orderBy('starts_at')->get(),
            'printedAt' => now(),
            'filters' => $request->only(['academic_year_id', 'semester_id', 'classroom_id', 'employee_id', 'day_of_week', 'entry_type']),
        ]);
    }

    public function export(): StreamedResponse
    {
        $rows = LessonSchedule::with(['employee', 'classroom', 'subject', 'semester'])->get();

        return response()->streamDownload(function () use ($rows): void {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Hari', 'Jam', 'Kelas', 'Mata Pelajaran', 'Guru', 'Ruangan', 'Semester']);
            foreach ($rows as $schedule) {
                fputcsv($file, [$schedule->day_of_week?->label(), substr($schedule->starts_at, 0, 5).'-'.substr($schedule->ends_at, 0, 5), $schedule->classroom?->name, $schedule->subject?->name, $schedule->employee?->name, $schedule->room, $schedule->semester?->name]);
            }
        }, 'jadwal-pelajaran.csv', ['Content-Type' => 'text/csv']);
    }

    public function uploadTemplate(LessonScheduleTemplateUploadRequest $request, LessonScheduleTemplateService $templates): RedirectResponse
    {
        $templates->storeTemplate($request->file('template'));

        return back()->with('status', 'Template jadwal berhasil diperbarui.');
    }

    public function downloadTemplate(LessonScheduleTemplateService $templates): BinaryFileResponse
    {
        $path = $templates->templatePath();
        abort_unless($path, 404, 'Template Word Jadwal Pelajaran belum tersedia.');

        return response()->download(Storage::disk('local')->path($path), 'JADWAL_PELAJARAN_TEMPLATE.docx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
    }

    public function exportWord(Request $request, LessonScheduleTemplateService $templates): BinaryFileResponse|RedirectResponse
    {
        $data = $request->validate([
            'tahun_ajaran_id' => ['required', 'exists:academic_years,id'], 'semester_id' => ['required', 'exists:semesters,id'],
            'tanggal_dokumen' => ['required', 'date'], 'kelas' => ['nullable', 'string'],
        ], ['tahun_ajaran_id.required' => 'Tahun ajaran wajib dipilih.', 'semester_id.required' => 'Semester wajib dipilih.', 'tanggal_dokumen.required' => 'Tanggal dokumen wajib diisi.']);
        if (! $templates->templatePath()) {
            return back()->withErrors(['template' => 'Template Word Jadwal Pelajaran belum diunggah.'])->withInput();
        }

        $classrooms = Classroom::with('homeroomTeacher')->where('is_active', true)->whereIn('code', LessonScheduleTemplateService::CLASS_CODES)
            ->when($data['kelas'] ?? null, fn (Builder $q, $code) => $q->where('code', $code))->get()->sortBy(fn ($c) => array_search($c->code, LessonScheduleTemplateService::CLASS_CODES, true))->values();
        if ($classrooms->isEmpty()) {
            return back()->withErrors(['kelas' => 'Tidak ada kelas resmi yang tersedia.'])->withInput();
        }
        $schedules = LessonSchedule::with(['classroom', 'subject', 'employee', 'teachingAssignment.subject', 'academicYear', 'semester'])
            ->where('academic_year_id', $data['tahun_ajaran_id'])->where('semester_id', $data['semester_id'])->where('is_active', true)
            ->whereIn('classroom_id', $classrooms->pluck('id'))->orderBy('classroom_id')->orderBy('day_of_week')->orderBy('starts_at')->get();
        if ($schedules->isEmpty()) {
            return back()->withErrors(['jadwal' => 'Tidak ada jadwal aktif untuk tahun ajaran dan semester yang dipilih.'])->withInput();
        }

        try {
            $year = AcademicYear::findOrFail($data['tahun_ajaran_id']);
            $semester = Semester::findOrFail($data['semester_id']);
            $path = $templates->render($schedules, $year, $semester, Carbon::parse($data['tanggal_dokumen']), $classrooms);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['export' => $exception->getMessage()])->withInput();
        }
        $yearName = preg_replace('/[^A-Za-z0-9-]+/', '_', $year->name);
        $semesterName = preg_replace('/[^A-Za-z0-9-]+/', '_', strtoupper($semester->name));

        return response()->download($path, "JADWAL_PELAJARAN_MI_MUSLIMAT_NU_DEMAK_{$yearName}_{$semesterName}.docx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])->deleteFileAfterSend(true);
    }

    private function authorizedQuery(Request $request, Builder $query): Builder
    {
        if ($request->user()?->can('schedules.view')) {
            return $query;
        }

        $employeeId = $this->viewOwnEmployeeId($request);

        return $employeeId ? $query->where('employee_id', $employeeId) : $query->whereRaw('1 = 0');
    }

    private function abortUnlessCanView(Request $request, ?int $employeeId): void
    {
        if ($request->user()?->can('schedules.view')) {
            return;
        }

        abort_unless($request->user()?->can('schedules.view-own') && $this->viewOwnEmployeeId($request) === $employeeId, 403);
    }

    private function viewOwnEmployeeId(Request $request): ?int
    {
        if ($request->user()?->can('schedules.view') || ! $request->user()?->can('schedules.view-own')) {
            return null;
        }

        return $request->user()->employee()->value('id');
    }

    private function refs(?int $employeeId = null): array
    {
        return [
            'academicYears' => AcademicYear::all(),
            'semesters' => Semester::all(),
            'classrooms' => Classroom::where('is_active', true)->get(),
            'employees' => Employee::where('is_active', true)->when($employeeId, fn (Builder $query) => $query->whereKey($employeeId))->get(),
            'subjects' => Subject::where('is_active', true)->get(),
            'assignments' => TeachingAssignment::with(['employee', 'classroom', 'subject', 'semester'])->where('is_active', true)->when($employeeId, fn (Builder $query) => $query->where('employee_id', $employeeId))->get(),
            'days' => DayOfWeek::cases(),
        ];
    }
}
