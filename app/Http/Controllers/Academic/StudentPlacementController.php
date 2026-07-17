<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academic;

use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\PlaceStudentsRequest;
use App\Http\Requests\Academic\TransferStudentRequest;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\ClassroomPlacementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentPlacementController extends Controller
{
    public function create(Request $request, Classroom $classroom): View
    {
        $placed = StudentEnrollment::where('academic_year_id',$classroom->academic_year_id)->where('enrollment_status',EnrollmentStatus::Active)->pluck('student_id');
        $students = Student::query()->where('is_active',true)->where('student_status',StudentStatus::Active)->whereNotIn('id',$placed)
            ->when($request->search, fn($q,$v)=>$q->where(fn($qq)=>$qq->where('name','like',"%$v%")->orWhere('student_number','like',"%$v%")->orWhere('national_student_number','like',"%$v%")))
            ->when($request->gender, fn($q,$v)=>$q->where('gender',$v))->orderBy('name')->paginate(20)->withQueryString();
        $classroom->loadCount('activeStudentEnrollments');
        return view('academic.classrooms.place',['classroom'=>$classroom,'students'=>$students]);
    }
    public function store(PlaceStudentsRequest $request, Classroom $classroom, ClassroomPlacementService $service): RedirectResponse
    { $override=$request->boolean('override_capacity') && $request->user()->can('student-enrollments.override-capacity'); $count=$service->place($classroom,$request->input('student_ids',[]),$request->validated(),$override); return redirect()->route('classrooms.show',$classroom)->with('status',"{$count} siswa berhasil ditempatkan."); }
    public function editTransfer(StudentEnrollment $enrollment): View
    { $targets=Classroom::where('academic_year_id',$enrollment->academic_year_id)->where('id','!=',$enrollment->classroom_id)->where('is_active',true)->orderBy('code')->get(); return view('academic.classrooms.transfer',['enrollment'=>$enrollment->load(['student','classroom']),'targets'=>$targets]); }
    public function transfer(TransferStudentRequest $request, StudentEnrollment $enrollment, ClassroomPlacementService $service): RedirectResponse
    { $target=Classroom::findOrFail($request->integer('target_classroom_id')); $new=$service->transfer($enrollment,$target,$request->validated()); return redirect()->route('classrooms.show',$new->classroom_id)->with('status','Siswa berhasil dipindahkan.'); }
}
