<?php

declare(strict_types=1);

namespace App\Http\Controllers\Attendance;

use App\Actions\Attendance\FinalizeClassAttendanceAction;
use App\Actions\Attendance\SaveClassAttendanceAction;
use App\Enums\StudentAttendanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\StudentAttendanceFilterRequest;
use App\Http\Requests\Attendance\StudentAttendanceRequest;
use App\Models\Classroom;
use App\Models\StudentAttendanceSession;
use App\Services\Attendance\StudentAttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StudentAttendanceController extends Controller
{
    public function index(StudentAttendanceFilterRequest $request, StudentAttendanceService $service): View
    {
        $classrooms = $service->classroomsFor($request->user());
        $sessions = StudentAttendanceSession::query()->with('classroom','attendances.student')->whereIn('classroom_id', $classrooms->pluck('id'))
            ->when($request->date, fn($q,$v)=>$q->whereDate('attendance_date',$v))->when($request->classroom_id, fn($q,$v)=>$q->where('classroom_id',$v))->latest('attendance_date')->paginate(15)->withQueryString();
        return view('attendance.students.index', ['sessions'=>$sessions,'classrooms'=>$classrooms,'statuses'=>StudentAttendanceStatus::options()]);
    }
    public function own(StudentAttendanceFilterRequest $request, StudentAttendanceService $service): View { return $this->index($request, $service); }
    public function create(StudentAttendanceFilterRequest $request, StudentAttendanceService $service): View
    {
        $date = $request->input('date', today()->toDateString());
        $classrooms = $service->classroomsFor($request->user());
        $classroom = $request->classroom_id ? $classrooms->firstWhere('id', (int) $request->classroom_id) : $classrooms->first();
        abort_if(! $classroom, 403); abort_unless($service->canAccessClassroom($request->user(), $classroom), 403);
        $session = $service->findOrCreateSession($classroom, $date, $request->user());
        $session->load('attendances.student','classroom.homeroomTeacher','academicYear','semester');
        return view('attendance.students.form', ['session'=>$session,'classrooms'=>$classrooms,'enrollments'=>$service->eligibleEnrollments($classroom,$date),'statuses'=>StudentAttendanceStatus::options(),'summary'=>$service->summary($session)]);
    }
    public function store(StudentAttendanceRequest $request, StudentAttendanceService $service, SaveClassAttendanceAction $save, FinalizeClassAttendanceAction $finalize): RedirectResponse
    {
        $classroom = $service->classroomsFor($request->user())->firstWhere('id', (int) $request->input('classroom_id')) ?? $service->classroomsFor($request->user())->first(); abort_if(! $classroom,403);
        $session = $service->findOrCreateSession($classroom, $request->validated('attendance_date'), $request->user());
        $save->execute($session, $request->validated('students'), $request->user());
        if ($request->input('action') === 'finalize') { $finalize->execute($session->refresh(), $request->user()); }
        return redirect()->route('student-attendances.show', $session)->with('status', $request->input('action') === 'finalize' ? 'Absensi siswa berhasil difinalisasi.' : 'Draft absensi siswa berhasil disimpan.');
    }
    public function show(StudentAttendanceSession $studentAttendance, StudentAttendanceService $service): View
    { abort_unless($service->canAccessClassroom(request()->user(), $studentAttendance->classroom), 403); $studentAttendance->load('classroom.homeroomTeacher','academicYear','semester','recorder','finalizer','attendances.student','attendances.corrections.corrector'); return view('attendance.students.show', ['session'=>$studentAttendance,'summary'=>$service->summary($studentAttendance),'statuses'=>StudentAttendanceStatus::options()]); }
    public function edit(StudentAttendanceSession $studentAttendance, StudentAttendanceService $service): View
    { request()->merge(['classroom_id'=>$studentAttendance->classroom_id,'date'=>$studentAttendance->attendance_date->toDateString()]); return $this->create(app(StudentAttendanceFilterRequest::class), $service); }
    public function finalize(StudentAttendanceSession $studentAttendance, FinalizeClassAttendanceAction $action): RedirectResponse { $action->execute($studentAttendance, request()->user()); return back()->with('status','Absensi siswa berhasil difinalisasi.'); }
    public function missing(StudentAttendanceFilterRequest $request, StudentAttendanceService $service): View { $date=$request->input('date', today()->toDateString()); return view('attendance.students.missing', ['date'=>$date,'classrooms'=>$service->missingClasses($date)]); }
}
