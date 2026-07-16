<?php

declare(strict_types=1);

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Models\{AssessmentComponent,Classroom,PredicateRange,StudentEnrollment,StudentScore,TeachingAssignment};
use App\Services\AssessmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function dashboard(): View { return view('assessments.reports.dashboard',['draft'=>AssessmentComponent::where('status','draft')->count(),'unpublished'=>AssessmentComponent::where('status','!=','published')->count(),'incomplete'=>AssessmentComponent::doesntHave('scores')->count()]); }
    public function index(): View { return view('assessments.components.index',['components'=>AssessmentComponent::latest()->paginate(15)]); }
    public function create(): View { return view('assessments.components.form',['component'=>new AssessmentComponent,'assignments'=>TeachingAssignment::where('is_active',true)->get()]); }
    public function store(Request $request): RedirectResponse { $data=$this->validateComponent($request); $assignment=TeachingAssignment::findOrFail($data['teaching_assignment_id']); $sum=AssessmentComponent::where('classroom_id',$assignment->classroom_id)->where('subject_id',$assignment->subject_id)->where('semester_id',$assignment->semester_id)->sum('weight'); abort_if($sum+$data['weight']>100,422,'Total bobot melebihi 100.'); AssessmentComponent::create($data+['academic_year_id'=>$assignment->academic_year_id,'semester_id'=>$assignment->semester_id,'classroom_id'=>$assignment->classroom_id,'subject_id'=>$assignment->subject_id,'employee_id'=>$assignment->employee_id,'created_by'=>auth()->id()]); return redirect()->route('assessment-components.index')->with('status','Komponen disimpan.'); }
    public function edit(AssessmentComponent $assessmentComponent): View { abort_if($assessmentComponent->status==='published' && ! auth()->user()->can('assessments.unlock'),403); return view('assessments.components.form',['component'=>$assessmentComponent,'assignments'=>TeachingAssignment::get()]); }
    public function update(Request $request, AssessmentComponent $assessmentComponent): RedirectResponse { abort_if($assessmentComponent->status==='published' && ! auth()->user()->can('assessments.unlock'),403); $assessmentComponent->update($this->validateComponent($request)); return redirect()->route('assessment-components.show',$assessmentComponent)->with('status','Komponen diperbarui.'); }
    public function show(AssessmentComponent $assessmentComponent): View { return view('assessments.components.show',['component'=>$assessmentComponent,'scores'=>StudentScore::where('assessment_component_id',$assessmentComponent->id)->get()]); }
    public function scores(AssessmentComponent $assessmentComponent): View { $students=StudentEnrollment::where('classroom_id',$assessmentComponent->classroom_id)->where('enrollment_status','active')->with('student')->get(); return view('assessments.scores.form',compact('assessmentComponent','students')); }
    public function storeScores(Request $request, AssessmentComponent $assessmentComponent, AssessmentService $service): RedirectResponse { abort_if($assessmentComponent->status==='published' && ! auth()->user()->can('assessments.unlock'),403); $service->storeScores($assessmentComponent,$request->input('scores',[]),auth()->id()); return back()->with('status','Nilai disimpan.'); }
    public function publish(AssessmentComponent $assessmentComponent): RedirectResponse { $assessmentComponent->update(['status'=>'published','published_at'=>now()]); activity('assessment')->performedOn($assessmentComponent)->event('assessment.component.published')->log('Komponen dipublish'); return back()->with('status','Nilai dipublish.'); }
    public function recap(): View { return view('assessments.reports.recap',['classes'=>Classroom::withCount('studentEnrollments')->get(),'components'=>AssessmentComponent::paginate(20)]); }
    public function predicates(): View { return view('assessments.predicates.index',['ranges'=>PredicateRange::orderBy('sequence')->get()]); }
    public function savePredicates(Request $request): RedirectResponse { foreach ($request->input('ranges',[]) as $id=>$data) { PredicateRange::find($id)?->update($data); } return back()->with('status','Rentang predikat diperbarui.'); }
    private function validateComponent(Request $request): array { return $request->validate(['teaching_assignment_id'=>'required|exists:teaching_assignments,id','name'=>'required','type'=>'required','weight'=>'required|numeric|min:0.01|max:100','maximum_score'=>'required|numeric|min:1','assessment_date'=>'nullable|date','description'=>'nullable','is_required'=>'boolean','status'=>'nullable']); }
}
