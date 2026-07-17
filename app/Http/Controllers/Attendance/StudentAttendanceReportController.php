<?php

declare(strict_types=1);

namespace App\Http\Controllers\Attendance;

use App\Enums\StudentAttendanceSessionStatus;
use App\Enums\StudentAttendanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\StudentAttendanceFilterRequest;
use App\Models\Classroom;
use App\Models\StudentAttendance;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudentAttendanceReportController extends Controller
{
    public function index(StudentAttendanceFilterRequest $request): View
    { return view('attendance.students.report', $this->data($request)); }
    public function print(StudentAttendanceFilterRequest $request): View
    { return view('attendance.students.print', $this->data($request)); }
    public function export(StudentAttendanceFilterRequest $request): Response
    { $rows=$this->query($request)->get(); $csv="Tanggal,Kelas,NIS,Nama,Status,Keterangan\n"; foreach($rows as $a){$csv.=implode(',',[$a->attendance_date?->format('Y-m-d'),$a->classroom?->name,$a->student?->nis,$a->student?->name,$a->status?->label(),str_replace(',',' ',$a->notes ?? '')])."\n";} activity('student-attendances')->causedBy($request->user())->log('Laporan absensi siswa diekspor'); return response($csv,200,['Content-Type'=>'text/csv','Content-Disposition'=>'attachment; filename="laporan-absensi-siswa.csv"']); }
    private function data(StudentAttendanceFilterRequest $request): array
    { $rows=$this->query($request)->paginate(25)->withQueryString(); $summary=$this->query($request)->select('status',DB::raw('count(*) total'))->groupBy('status')->pluck('total','status'); return ['rows'=>$rows,'summary'=>$summary,'statuses'=>StudentAttendanceStatus::options(),'classrooms'=>Classroom::query()->where('is_active',true)->orderBy('name')->get()]; }
    private function query(StudentAttendanceFilterRequest $request)
    { return StudentAttendance::query()->with('student','classroom','session')->whereHas('session',fn($q)=>$q->where('status',StudentAttendanceSessionStatus::Final->value))->when($request->from,fn($q,$v)=>$q->whereDate('attendance_date','>=',$v))->when($request->to,fn($q,$v)=>$q->whereDate('attendance_date','<=',$v))->when($request->classroom_id,fn($q,$v)=>$q->where('classroom_id',$v))->when($request->student_id,fn($q,$v)=>$q->where('student_id',$v))->when($request->status,fn($q,$v)=>$q->where('status',$v))->latest('attendance_date'); }
}
