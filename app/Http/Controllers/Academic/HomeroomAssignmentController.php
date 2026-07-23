<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academic;

use App\Enums\EmploymentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\HomeroomAssignmentRequest;
use App\Models\Classroom;
use App\Models\Employee;
use App\Services\ClassroomPlacementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeroomAssignmentController extends Controller
{
    public function edit(Classroom $classroom): View
    { $employees=Employee::where('is_active',true)->where('employment_type',EmploymentType::ClassTeacher)->orderBy('name')->get(); return view('academic.classrooms.homeroom',['classroom'=>$classroom->load('activeHomeroomAssignment.employee'),'employees'=>$employees]); }
    public function update(HomeroomAssignmentRequest $request, Classroom $classroom, ClassroomPlacementService $service): RedirectResponse
    { $service->assignHomeroom($classroom,Employee::findOrFail($request->integer('employee_id')),$request->validated()); return redirect()->route('classrooms.show',$classroom)->with('status','Wali kelas berhasil ditetapkan.'); }
    public function destroy(Request $request, Classroom $classroom, ClassroomPlacementService $service): RedirectResponse
    { $request->validate(['ended_at'=>['required','date'],'reason'=>['required','string','max:1000'],'notes'=>['nullable','string']]); $service->releaseHomeroom($classroom,$request->all()); return redirect()->route('classrooms.show',$classroom)->with('status','Wali kelas berhasil dilepas.'); }
}
