<?php

declare(strict_types=1);

namespace App\Http\Controllers\Attendance;

use App\Actions\Attendance\CorrectStudentAttendanceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\StudentAttendanceCorrectionRequest;
use App\Models\StudentAttendance;
use App\Services\Attendance\StudentAttendanceService;
use Illuminate\Http\RedirectResponse;

class StudentAttendanceCorrectionController extends Controller
{
    public function store(StudentAttendanceCorrectionRequest $request, StudentAttendance $attendance, CorrectStudentAttendanceAction $action, StudentAttendanceService $service): RedirectResponse
    { abort_unless($service->canAccessClassroom($request->user(), $attendance->classroom), 403); $action->execute($attendance, $request->validated(), $request->user()); return back()->with('status','Koreksi absensi berhasil disimpan.'); }
}
