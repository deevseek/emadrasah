<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academic;

use App\Enums\DayOfWeek;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\LessonScheduleRequest;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\LessonSchedule;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Services\Academic\ScheduleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedResponse;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $employeeId = $this->viewOwnEmployeeId($request);
        $query = $this->authorizedQuery($request, LessonSchedule::query())
            ->with(['academicYear', 'semester', 'employee', 'classroom', 'subject', 'teachingAssignment'])
            ->when($request->academic_year_id, fn (Builder $query, mixed $value) => $query->where('academic_year_id', $value))
            ->when($request->semester_id, fn (Builder $query, mixed $value) => $query->where('semester_id', $value))
            ->when($request->classroom_id, fn (Builder $query, mixed $value) => $query->where('classroom_id', $value))
            ->when($employeeId ?? $request->employee_id, fn (Builder $query, mixed $value) => $query->where('employee_id', $value))
            ->when($request->subject_id, fn (Builder $query, mixed $value) => $query->where('subject_id', $value))
            ->when($request->day_of_week, fn (Builder $query, mixed $value) => $query->where('day_of_week', $value));
        $weekly = (clone $query)->orderBy('day_of_week')->orderBy('starts_at')->get()->groupBy(fn (LessonSchedule $schedule) => $schedule->day_of_week->value);

        return view('schedules.index', $this->refs($employeeId) + [
            'schedules' => $query->orderBy('day_of_week')->orderBy('starts_at')->paginate(15)->withQueryString(),
            'weekly' => $weekly,
            'filters' => $request->all(),
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
                ->when($request->classroom_id, fn (Builder $query, mixed $value) => $query->where('classroom_id', $value))
                ->when($employeeId ?? $request->employee_id, fn (Builder $query, mixed $value) => $query->where('employee_id', $value))
                ->orderBy('day_of_week')->orderBy('starts_at')->get(),
            'printedAt' => now(),
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

    private function authorizedQuery(Request $request, Builder $query): Builder
    {
        if ($request->user()?->can('schedules.view')) {
            return $query;
        }

        $employeeId = $this->viewOwnEmployeeId($request);

        return $employeeId ? $query->where('employee_id', $employeeId) : $query->whereRaw('1 = 0');
    }

    private function abortUnlessCanView(Request $request, int $employeeId): void
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
