<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academic;

use App\Enums\DayOfWeek; use App\Enums\EmployeeStatus; use App\Enums\EmploymentType; use App\Enums\Gender; use App\Enums\SubjectCategory;
use App\Http\Controllers\Controller; use App\Http\Requests\Academic\ClassroomRequest; use App\Http\Requests\Academic\EmployeeRequest; use App\Http\Requests\Academic\GradeLevelRequest; use App\Http\Requests\Academic\LessonScheduleRequest; use App\Http\Requests\Academic\SubjectRequest; use App\Http\Requests\Academic\TeachingAssignmentRequest;
use App\Models\AcademicYear; use App\Models\Classroom; use App\Models\Employee; use App\Models\GradeLevel; use App\Models\LessonSchedule; use App\Models\Semester; use App\Models\Subject; use App\Models\TeachingAssignment; use App\Models\User;
use App\Services\Academic\ScheduleConflictService; use App\Services\ActivityLogger; use Illuminate\Database\Eloquent\Model; use Illuminate\Http\RedirectResponse; use Illuminate\Http\Request; use Illuminate\Support\Facades\DB; use Illuminate\View\View;

class AcademicResourceController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}
    public function gradeLevels(Request $request): View { return $this->index($request, GradeLevel::query(), 'grade-levels', 'Tingkat Kelas', ['name','code']); }
    public function createGradeLevel(): View { return $this->form('grade-levels','Tingkat Kelas', new GradeLevel, ['grade_level'=>null]); }
    public function storeGradeLevel(GradeLevelRequest $request): RedirectResponse { return $this->store($request->validated(), new GradeLevel, 'grade-levels'); }
    public function editGradeLevel(GradeLevel $gradeLevel): View { return $this->form('grade-levels','Tingkat Kelas', $gradeLevel, ['grade_level'=>$gradeLevel]); }
    public function updateGradeLevel(GradeLevelRequest $request, GradeLevel $gradeLevel): RedirectResponse { return $this->update($request->validated(), $gradeLevel, 'grade-levels'); }
    public function destroyGradeLevel(GradeLevel $gradeLevel): RedirectResponse { if ($gradeLevel->classrooms()->exists()) { $gradeLevel->update(['is_active'=>false]); return back()->with('status','Tingkat kelas sudah dipakai sehingga dinonaktifkan.'); } return $this->delete($gradeLevel,'grade-levels'); }

    public function classrooms(Request $request): View { return $this->index($request, Classroom::with(['academicYear','gradeLevel','homeroomTeacher']), 'classrooms', 'Kelas', ['name','code'], ['academic_year_id']); }
    public function createClassroom(): View { return $this->form('classrooms','Kelas', new Classroom, $this->refs()); }
    public function storeClassroom(ClassroomRequest $request): RedirectResponse { return $this->store($request->validated(), new Classroom, 'classrooms'); }
    public function editClassroom(Classroom $classroom): View { return $this->form('classrooms','Kelas', $classroom, $this->refs()+['classroom'=>$classroom]); }
    public function updateClassroom(ClassroomRequest $request, Classroom $classroom): RedirectResponse { return $this->update($request->validated(), $classroom, 'classrooms'); }
    public function destroyClassroom(Classroom $classroom): RedirectResponse { if ($classroom->teachingAssignments()->exists() || $classroom->schedules()->exists()) { $classroom->update(['is_active'=>false]); return back()->with('status','Kelas sudah memiliki relasi sehingga dinonaktifkan.'); } return $this->delete($classroom,'classrooms'); }

    public function subjects(Request $request): View { return $this->index($request, Subject::query(), 'subjects', 'Mata Pelajaran', ['name','code']); }
    public function createSubject(): View { return $this->form('subjects','Mata Pelajaran', new Subject, ['categories'=>SubjectCategory::cases()]); }
    public function storeSubject(SubjectRequest $request): RedirectResponse { return $this->store($request->validated(), new Subject, 'subjects'); }
    public function editSubject(Subject $subject): View { return $this->form('subjects','Mata Pelajaran', $subject, ['subject'=>$subject,'categories'=>SubjectCategory::cases()]); }
    public function updateSubject(SubjectRequest $request, Subject $subject): RedirectResponse { return $this->update($request->validated(), $subject, 'subjects'); }
    public function destroySubject(Subject $subject): RedirectResponse { if ($subject->teachingAssignments()->exists() || $subject->schedules()->exists()) { $subject->update(['is_active'=>false]); return back()->with('status','Mata pelajaran sudah dipakai sehingga dinonaktifkan.'); } return $this->delete($subject,'subjects'); }

    public function employees(Request $request): View { return $this->index($request, Employee::with('user'), 'employees', 'Pegawai', ['name','employee_number','national_identity_number']); }
    public function createEmployee(): View { return $this->form('employees','Pegawai', new Employee, $this->employeeRefs()); }
    public function storeEmployee(EmployeeRequest $request): RedirectResponse { return DB::transaction(function () use ($request) { $data=$request->validated(); if($request->hasFile('photo')) $data['photo_path']=$request->file('photo')->store('employee-photos','public'); unset($data['photo']); return $this->store($data,new Employee,'employees'); }); }
    public function editEmployee(Employee $employee): View { return $this->form('employees','Pegawai', $employee, $this->employeeRefs()+['employee'=>$employee]); }
    public function updateEmployee(EmployeeRequest $request, Employee $employee): RedirectResponse { return DB::transaction(function () use ($request,$employee) { $data=$request->validated(); if($request->hasFile('photo')) $data['photo_path']=$request->file('photo')->store('employee-photos','public'); unset($data['photo']); return $this->update($data,$employee,'employees'); }); }
    public function destroyEmployee(Employee $employee): RedirectResponse { if ($employee->teachingAssignments()->exists() || $employee->schedules()->exists() || $employee->homeroomClassrooms()->exists()) { $employee->update(['is_active'=>false]); return back()->with('status','Pegawai sudah memiliki relasi sehingga dinonaktifkan.'); } return $this->delete($employee,'employees'); }

    public function teachingAssignments(Request $request): View { return $this->index($request, TeachingAssignment::with(['academicYear','semester','employee','classroom','subject']), 'teaching-assignments', 'Penugasan Mengajar', ['employee.name','classroom.name','subject.name'], ['academic_year_id']); }
    public function createTeachingAssignment(): View { return $this->form('teaching-assignments','Penugasan Mengajar', new TeachingAssignment, $this->refs()+['semesters'=>Semester::all(),'subjects'=>Subject::where('is_active',true)->get()]); }
    public function storeTeachingAssignment(TeachingAssignmentRequest $request): RedirectResponse { return $this->store($request->validated(), new TeachingAssignment, 'teaching-assignments'); }
    public function editTeachingAssignment(TeachingAssignment $teachingAssignment): View { return $this->form('teaching-assignments','Penugasan Mengajar', $teachingAssignment, $this->refs()+['semesters'=>Semester::all(),'subjects'=>Subject::all(),'teaching_assignment'=>$teachingAssignment]); }
    public function updateTeachingAssignment(TeachingAssignmentRequest $request, TeachingAssignment $teachingAssignment): RedirectResponse { return $this->update($request->validated(), $teachingAssignment, 'teaching-assignments'); }
    public function destroyTeachingAssignment(TeachingAssignment $teachingAssignment): RedirectResponse { return $this->delete($teachingAssignment,'teaching-assignments'); }

    public function schedules(Request $request): View { return $this->index($request, LessonSchedule::with(['academicYear','semester','employee','classroom','subject']), 'schedules', 'Jadwal Pelajaran', ['employee.name','classroom.name','subject.name'], ['academic_year_id','semester_id','classroom_id','employee_id','day_of_week']); }
    public function createSchedule(): View { return $this->form('schedules','Jadwal Pelajaran', new LessonSchedule, $this->refs()+['semesters'=>Semester::all(),'subjects'=>Subject::all(),'days'=>DayOfWeek::cases()]); }
    public function storeSchedule(LessonScheduleRequest $request, ScheduleConflictService $service): RedirectResponse { $data=$request->validated(); $service->validate($data); return $this->store($data, new LessonSchedule, 'schedules'); }
    public function editSchedule(LessonSchedule $schedule): View { return $this->form('schedules','Jadwal Pelajaran', $schedule, $this->refs()+['semesters'=>Semester::all(),'subjects'=>Subject::all(),'days'=>DayOfWeek::cases(),'schedule'=>$schedule]); }
    public function updateSchedule(LessonScheduleRequest $request, LessonSchedule $schedule, ScheduleConflictService $service): RedirectResponse { $data=$request->validated(); $service->validate($data,$schedule); return $this->update($data, $schedule, 'schedules'); }
    public function destroySchedule(LessonSchedule $schedule): RedirectResponse { return $this->delete($schedule,'schedules'); }

    private function index(Request $request, $query, string $key, string $title, array $search, array $filters=[]): View { $query->when($request->filled('status'), fn($q)=>$q->where('is_active',$request->status==='active'))->when($request->filled('academic_year_id'), fn($q)=>$q->where('academic_year_id',$request->academic_year_id)); if($request->filled('q')){ $term='%'.$request->q.'%'; $query->where(function($q) use($search,$term){ foreach($search as $col){ if(str_contains($col,'.')) continue; $q->orWhere($col,'like',$term); } }); } return view('academic.master.index', ['items'=>$query->latest()->paginate(10)->withQueryString(),'key'=>$key,'title'=>$title,'academicYears'=>AcademicYear::all(),'days'=>DayOfWeek::cases()]); }
    private function form(string $key, string $title, Model $model, array $data=[]): View { return view('academic.master.form', $data+['key'=>$key,'title'=>$title,'model'=>$model]); }
    private function store(array $data, Model $model, string $key): RedirectResponse { $model->fill($data+['is_active'=>request()->boolean('is_active', true)])->save(); $this->logger->log($key.'.create',$model,[], $model->getAttributes()); return redirect()->route($key.'.index')->with('status','Data berhasil ditambahkan.'); }
    private function update(array $data, Model $model, string $key): RedirectResponse { $old=$model->getOriginal(); $model->fill($data+['is_active'=>request()->boolean('is_active')])->save(); $this->logger->log($key.'.update',$model,$old,$model->getAttributes()); return redirect()->route($key.'.index')->with('status','Data berhasil diperbarui.'); }
    private function delete(Model $model, string $key): RedirectResponse { $old=$model->getAttributes(); $model->delete(); $this->logger->log($key.'.delete',$model,$old,[]); return back()->with('status','Data berhasil dihapus.'); }
    private function refs(): array { return ['academicYears'=>AcademicYear::all(),'gradeLevels'=>GradeLevel::where('is_active',true)->get(),'employees'=>Employee::where('is_active',true)->get(),'classrooms'=>Classroom::where('is_active',true)->get()]; }
    private function employeeRefs(): array { return ['users'=>User::doesntHave('employee')->get(),'genders'=>Gender::cases(),'employmentTypes'=>EmploymentType::cases(),'employeeStatuses'=>EmployeeStatus::cases()]; }
}
