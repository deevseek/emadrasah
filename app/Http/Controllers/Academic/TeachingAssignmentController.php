<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\TeachingAssignmentRequest;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Services\Academic\TeacherWorkloadService;
use App\Services\Academic\TeachingAssignmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedResponse;
use Illuminate\View\View;

class TeachingAssignmentController extends Controller
{
    public function index(Request $request, TeacherWorkloadService $workloadService): View
    {
        $employeeId = $this->viewOwnEmployeeId($request);
        $query = $this->authorizedQuery($request, TeachingAssignment::query())
            ->with(['academicYear', 'semester', 'employee', 'classroom', 'subject', 'schedules'])
            ->when($request->academic_year_id, fn (Builder $query, mixed $value) => $query->where('academic_year_id', $value))
            ->when($request->semester_id, fn (Builder $query, mixed $value) => $query->where('semester_id', $value))
            ->when($request->classroom_id, fn (Builder $query, mixed $value) => $query->where('classroom_id', $value))
            ->when($employeeId ?? $request->employee_id, fn (Builder $query, mixed $value) => $query->where('employee_id', $value))
            ->when($request->subject_id, fn (Builder $query, mixed $value) => $query->where('subject_id', $value))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('is_active', $request->boolean('status')));

        return view('teaching-assignments.index', $this->refs($employeeId) + [
            'assignments' => $query->paginate(15)->withQueryString(),
            'workloads' => $workloadService->summarize($employeeId),
            'filters' => $request->all(),
        ]);
    }

    public function create(): View
    {
        return view('teaching-assignments.form', $this->refs() + ['assignment' => new TeachingAssignment]);
    }

    public function store(TeachingAssignmentRequest $request, TeachingAssignmentService $service): RedirectResponse
    {
        $assignment = $service->save($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        return redirect()->route('teaching-assignments.show', $assignment)->with('status', 'Penugasan Mengajar berhasil disimpan.');
    }

    public function show(Request $request, TeachingAssignment $teachingAssignment): View
    {
        $this->abortUnlessCanView($request, $teachingAssignment->employee_id);

        return view('teaching-assignments.show', ['assignment' => $teachingAssignment->load(['academicYear', 'semester', 'employee', 'classroom', 'subject', 'schedules'])]);
    }

    public function edit(TeachingAssignment $teachingAssignment): View
    {
        return view('teaching-assignments.form', $this->refs() + ['assignment' => $teachingAssignment]);
    }

    public function update(TeachingAssignmentRequest $request, TeachingAssignment $teachingAssignment, TeachingAssignmentService $service): RedirectResponse
    {
        $service->save($request->validated() + ['is_active' => $request->boolean('is_active')], $teachingAssignment);

        return redirect()->route('teaching-assignments.show', $teachingAssignment)->with('status', 'Penugasan Mengajar diperbarui.');
    }

    public function toggle(TeachingAssignment $teachingAssignment, TeachingAssignmentService $service): RedirectResponse
    {
        $service->toggle($teachingAssignment, ! $teachingAssignment->is_active);

        return back()->with('status', 'Status penugasan diperbarui.');
    }

    public function destroy(TeachingAssignment $teachingAssignment): RedirectResponse
    {
        if ($teachingAssignment->schedules()->exists()) {
            app(TeachingAssignmentService::class)->toggle($teachingAssignment, false);

            return back()->with('status', 'Penugasan memiliki jadwal sehingga dinonaktifkan.');
        }

        $teachingAssignment->delete();

        return redirect()->route('teaching-assignments.index')->with('status', 'Penugasan dihapus.');
    }

    public function export(): StreamedResponse
    {
        $rows = TeachingAssignment::with(['employee', 'classroom', 'subject', 'academicYear', 'semester'])->get();

        return response()->streamDownload(function () use ($rows): void {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Guru', 'Kelas', 'Mata Pelajaran', 'Tahun Ajaran', 'Semester', 'Jam per Minggu', 'Status']);
            foreach ($rows as $assignment) {
                fputcsv($file, [$assignment->employee?->name, $assignment->classroom?->name, $assignment->subject?->name, $assignment->academicYear?->name, $assignment->semester?->name, $assignment->weekly_hours, $assignment->is_active ? 'Aktif' : 'Nonaktif']);
            }
        }, 'penugasan-mengajar.csv', ['Content-Type' => 'text/csv']);
    }

    private function authorizedQuery(Request $request, Builder $query): Builder
    {
        if ($request->user()?->can('teaching-assignments.view')) {
            return $query;
        }

        $employeeId = $this->viewOwnEmployeeId($request);

        return $employeeId ? $query->where('employee_id', $employeeId) : $query->whereRaw('1 = 0');
    }

    private function abortUnlessCanView(Request $request, int $employeeId): void
    {
        if ($request->user()?->can('teaching-assignments.view')) {
            return;
        }

        abort_unless($request->user()?->can('teaching-assignments.view-own') && $this->viewOwnEmployeeId($request) === $employeeId, 403);
    }

    private function viewOwnEmployeeId(Request $request): ?int
    {
        if ($request->user()?->can('teaching-assignments.view') || ! $request->user()?->can('teaching-assignments.view-own')) {
            return null;
        }

        return $request->user()->employee()->value('id');
    }

    private function refs(?int $employeeId = null): array
    {
        return [
            'academicYears' => AcademicYear::all(),
            'semesters' => Semester::all(),
            'employees' => Employee::where('is_active', true)->when($employeeId, fn (Builder $query) => $query->whereKey($employeeId))->get(),
            'classrooms' => Classroom::where('is_active', true)->get(),
            'subjects' => Subject::where('is_active', true)->get(),
        ];
    }
}
