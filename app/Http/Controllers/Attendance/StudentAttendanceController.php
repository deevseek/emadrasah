<?php

declare(strict_types=1);

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\StudentAttendanceBulkRequest;
use App\Models\Classroom;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Services\Attendance\StudentAttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class StudentAttendanceController extends Controller
{
    public function index(): View
    {
        return view('attendance.students.index', [
            'classrooms' => Classroom::query()->where('is_active', true)->orderBy('name')->get(),
            'records' => StudentAttendance::query()
                ->with('student', 'classroom')
                ->when(request('classroom_id'), fn ($query, $classroomId) => $query->where('classroom_id', $classroomId))
                ->when(request('date'), fn ($query, $date) => $query->whereDate('attendance_date', $date))
                ->latest('attendance_date')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        $classroom = request('classroom_id') ? Classroom::query()->where('is_active', true)->find(request('classroom_id')) : null;

        return view('attendance.students.form', [
            'classrooms' => Classroom::query()->where('is_active', true)->orderBy('name')->get(),
            'classroom' => $classroom,
            'enrollments' => $classroom === null
                ? collect()
                : StudentEnrollment::query()
                    ->with('student')
                    ->where('classroom_id', $classroom->id)
                    ->where('enrollment_status', 'active')
                    ->get(),
        ]);
    }

    public function store(StudentAttendanceBulkRequest $request, StudentAttendanceService $service): RedirectResponse
    {
        $validated = $request->validated();
        $service->bulk(Classroom::query()->findOrFail($validated['classroom_id']), $validated['attendance_date'], $validated['students']);

        return redirect()->route('student-attendances.index', [
            'classroom_id' => $validated['classroom_id'],
            'date' => $validated['attendance_date'],
        ])->with('status', 'Absensi siswa tersimpan.');
    }
}
