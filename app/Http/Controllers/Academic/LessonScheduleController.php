<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\LessonScheduleRequest;
use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\LessonSchedule;
use App\Models\Personnel;
use App\Models\Semester;
use App\Services\Academic\LessonScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LessonScheduleController extends Controller
{
    public function index(): View
    {
        return view('academic.lesson-schedules.index', [
            'schedules' => LessonSchedule::with(['academicYear', 'semester', 'classroom.gradeLevel', 'subject', 'personnel'])->latest()->paginate(30),
            'years' => AcademicYear::latest('starts_at')->get(),
            'semesters' => Semester::with('academicYear')->get(),
            'rooms' => Classroom::with(['gradeLevel', 'academicYear'])->where('is_active', true)->get(),
            'subjects' => AcademicSubject::where('is_active', true)->orderBy('name')->get(),
            'teachers' => Personnel::where('is_active', true)->orderBy('full_name')->get(),
        ]);
    }

    public function store(LessonScheduleRequest $request, LessonScheduleService $service): RedirectResponse
    {
        DB::transaction(function () use ($request, $service): void {
            $data = $request->validated();
            $data['start_time'] .= ':00';
            $data['end_time'] .= ':00';
            Classroom::whereKey($data['classroom_id'])->lockForUpdate()->firstOrFail();
            $service->ensureNoConflict($data);
            LessonSchedule::create($data + ['active' => true, 'created_by' => $request->user()->id]);
        });

        return back()->with('success', 'Jadwal dan penugasan mengajar berhasil ditambahkan.');
    }

    public function deactivate(Request $request, LessonSchedule $lessonSchedule): RedirectResponse
    {
        $lessonSchedule->update(['active' => false]);

        return back()->with('success', 'Penugasan dinonaktifkan. Jurnal lama tetap tersimpan.');
    }
}
