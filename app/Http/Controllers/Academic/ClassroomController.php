<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academic;

use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreClassroomRequest;
use App\Http\Requests\Academic\UpdateClassroomRequest;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\GradeLevel;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class ClassroomController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function index(Request $request): View
    {
        $classrooms = Classroom::query()->with(['academicYear','gradeLevel','homeroomTeacher'])->withCount(['activeStudentEnrollments','studentEnrollments'])
            ->when($request->search, fn($q,$v)=>$q->where(fn($qq)=>$qq->where('name','like',"%$v%")->orWhere('code','like',"%$v%")))
            ->when($request->academic_year_id, fn($q,$v)=>$q->where('academic_year_id',$v))
            ->when($request->grade_level_id, fn($q,$v)=>$q->where('grade_level_id',$v))
            ->when($request->status !== null && $request->status !== '', fn($q)=>$q->where('is_active',$request->status === 'active'))
            ->when($request->homeroom === 'none', fn($q)=>$q->whereNull('homeroom_teacher_id'))
            ->orderByDesc('academic_year_id')->orderBy('code')->paginate(15)->withQueryString();
        return view('academic.classrooms.index',['classrooms'=>$classrooms,'academicYears'=>AcademicYear::orderByDesc('starts_at')->get(),'gradeLevels'=>GradeLevel::orderBy('level')->get()]);
    }

    public function create(): View { return view('academic.classrooms.form',['classroom'=>new Classroom,'academicYears'=>AcademicYear::orderByDesc('starts_at')->get(),'gradeLevels'=>GradeLevel::where('is_active',true)->orderBy('level')->get()]); }
    public function store(StoreClassroomRequest $request): RedirectResponse { $classroom=Classroom::create($request->validated()+['is_active'=>$request->boolean('is_active',true)]); $this->logger->log('classroom.created',$classroom,[],$classroom->getAttributes(),'Kelas dibuat.'); return redirect()->route('classrooms.show',$classroom)->with('status','Kelas/Rombel berhasil dibuat.'); }
    public function show(Classroom $classroom): View
    {
        $classroom->load(['academicYear','gradeLevel','homeroomTeacher','homeroomAssignments.employee'])->loadCount(['activeStudentEnrollments','studentEnrollments']);
        return view('academic.classrooms.show',['classroom'=>$classroom,'activeEnrollments'=>$classroom->studentEnrollments()->where('enrollment_status',EnrollmentStatus::Active)->with('student')->orderBy('enrolled_at')->get(),'historyEnrollments'=>$classroom->studentEnrollments()->with('student')->latest()->paginate(20),'activities'=>\App\Models\ActivityLog::latest()->limit(10)->get()]);
    }
    public function edit(Classroom $classroom): View { return view('academic.classrooms.form',['classroom'=>$classroom,'academicYears'=>AcademicYear::orderByDesc('starts_at')->get(),'gradeLevels'=>GradeLevel::where('is_active',true)->orderBy('level')->get()]); }
    public function update(UpdateClassroomRequest $request, Classroom $classroom): RedirectResponse { $old=$classroom->getAttributes(); $classroom->update($request->validated()+['is_active'=>$request->boolean('is_active')]); $this->logger->log('classroom.updated',$classroom,$old,$classroom->getAttributes(),'Kelas diperbarui.'); return redirect()->route('classrooms.show',$classroom)->with('status','Kelas/Rombel berhasil diperbarui.'); }
    public function toggle(Classroom $classroom): RedirectResponse { $old=$classroom->getAttributes(); $classroom->update(['is_active'=>!$classroom->is_active]); $this->logger->log('classroom.status',$classroom,$old,$classroom->getAttributes(),'Status kelas diubah.'); return back()->with('status','Status kelas diperbarui.'); }
    public function destroy(Classroom $classroom): RedirectResponse { if($classroom->studentEnrollments()->exists() || $classroom->homeroomAssignments()->exists()){ $classroom->update(['is_active'=>false]); return back()->with('status','Kelas memiliki riwayat sehingga dinonaktifkan, bukan dihapus.'); } $classroom->delete(); return redirect()->route('classrooms.index')->with('status','Kelas dihapus.'); }
    public function export(Request $request): StreamedResponse { $rows=Classroom::with(['academicYear','gradeLevel','homeroomTeacher'])->withCount('activeStudentEnrollments')->get(); return response()->streamDownload(function() use($rows){ $f=fopen('php://output','w'); fputcsv($f,['Kode Rombel','Tingkat','Tahun Ajaran','Wali Kelas','Kapasitas','Jumlah Siswa','Status']); foreach($rows as $c) fputcsv($f,[$c->code,$c->gradeLevel?->name,$c->academicYear?->name,$c->homeroomTeacher?->name,$c->capacity,$c->active_student_enrollments_count,$c->is_active?'Aktif':'Nonaktif']); fclose($f); },'kelas-rombel.csv',['Content-Type'=>'text/csv']); }
    public function exportStudents(Classroom $classroom): StreamedResponse { $rows=$classroom->studentEnrollments()->with('student')->get(); return response()->streamDownload(function() use($rows){ $f=fopen('php://output','w'); fputcsv($f,['Nama','NIS','NISN','Jenis Kelamin','Status Penempatan','Tanggal Mulai']); foreach($rows as $e) fputcsv($f,[$e->student?->name,$e->student?->student_number,$e->student?->national_student_number,$e->student?->gender?->label(),$e->enrollment_status?->label(),optional($e->enrolled_at)->format('Y-m-d')]); fclose($f); },'siswa-kelas.csv',['Content-Type'=>'text/csv']); }
}
