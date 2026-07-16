<?php

declare(strict_types=1);

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Assessment\StoreAssessmentComponentRequest;
use App\Http\Requests\Assessment\UpdateAssessmentComponentRequest;
use App\Models\{AssessmentComponent,Classroom,PredicateRange,StudentEnrollment,StudentScore,TeachingAssignment};
use App\Services\AssessmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function dashboard(): View
    {
        return view('assessments.reports.dashboard', [
            'metrics' => [
                'draft' => AssessmentComponent::where('status', 'draft')->count(),
                'published' => AssessmentComponent::where('status', 'published')->count(),
                'incompleteComponents' => AssessmentComponent::doesntHave('scores')->count(),
                'unscoredStudents' => StudentEnrollment::where('enrollment_status', 'active')->whereNotIn('student_id', StudentScore::select('student_id'))->count(),
                'belowKkm' => StudentScore::whereNotNull('final_score')->where('final_score', '<', 75)->count(),
                'remedial' => StudentScore::whereNotNull('remedial_score')->count(),
            ],
            'classProgress' => Classroom::query()
                ->leftJoin('student_enrollments', 'classrooms.id', '=', 'student_enrollments.classroom_id')
                ->leftJoin('student_scores', 'student_enrollments.student_id', '=', 'student_scores.student_id')
                ->select('classrooms.name', DB::raw('count(distinct student_enrollments.student_id) as students_count'), DB::raw('count(student_scores.id) as scores_count'))
                ->groupBy('classrooms.id', 'classrooms.name')
                ->orderBy('classrooms.name')
                ->limit(6)
                ->get(),
        ]);
    }
    public function index(): View { return view('assessments.components.index',['components'=>AssessmentComponent::with(['classroom','subject'])->latest()->paginate(15)]); }
    public function create(): View { return view('assessments.components.form',['component'=>new AssessmentComponent,'assignments'=>TeachingAssignment::with(['classroom','subject'])->where('is_active',true)->get()]); }
    public function store(StoreAssessmentComponentRequest $request): RedirectResponse { $data=$request->validated(); $assignment=TeachingAssignment::findOrFail($data['teaching_assignment_id']); $sum=AssessmentComponent::where('classroom_id',$assignment->classroom_id)->where('subject_id',$assignment->subject_id)->where('semester_id',$assignment->semester_id)->sum('weight'); abort_if($sum+$data['weight']>100,422,'Total bobot melebihi 100.'); AssessmentComponent::create($data+['academic_year_id'=>$assignment->academic_year_id,'semester_id'=>$assignment->semester_id,'classroom_id'=>$assignment->classroom_id,'subject_id'=>$assignment->subject_id,'employee_id'=>$assignment->employee_id,'created_by'=>auth()->id()]); return redirect()->route('assessment-components.index')->with('status','Komponen disimpan.'); }
    public function edit(AssessmentComponent $assessmentComponent): View { abort_if($assessmentComponent->status==='published' && ! auth()->user()->can('assessments.unlock'),403); return view('assessments.components.form',['component'=>$assessmentComponent,'assignments'=>TeachingAssignment::with(['classroom','subject'])->get()]); }
    public function update(UpdateAssessmentComponentRequest $request, AssessmentComponent $assessmentComponent): RedirectResponse { abort_if($assessmentComponent->status==='published' && ! auth()->user()->can('assessments.unlock'),403); $assessmentComponent->update($request->validated()); return redirect()->route('assessment-components.show',$assessmentComponent)->with('status','Komponen diperbarui.'); }
    public function show(AssessmentComponent $assessmentComponent): View { return view('assessments.components.show',['component'=>$assessmentComponent,'scores'=>StudentScore::with('student')->where('assessment_component_id',$assessmentComponent->id)->get()]); }
    public function scores(AssessmentComponent $assessmentComponent): View { $students=StudentEnrollment::where('classroom_id',$assessmentComponent->classroom_id)->where('enrollment_status','active')->with('student')->get(); return view('assessments.scores.form',compact('assessmentComponent','students')); }
    public function storeScores(Request $request, AssessmentComponent $assessmentComponent, AssessmentService $service): RedirectResponse { abort_if($assessmentComponent->status==='published' && ! auth()->user()->can('assessments.unlock'),403); $service->storeScores($assessmentComponent,$request->input('scores',[]),auth()->id()); return back()->with('status','Nilai disimpan.'); }
    public function publish(AssessmentComponent $assessmentComponent): RedirectResponse { $assessmentComponent->update(['status'=>'published','published_at'=>now()]); activity('assessment')->performedOn($assessmentComponent)->event('assessment.component.published')->log('Komponen dipublish'); return back()->with('status','Nilai dipublish.'); }
    public function recap(): View { return view('assessments.reports.recap',['classes'=>Classroom::withCount('studentEnrollments')->get(),'components'=>AssessmentComponent::paginate(20)]); }
    public function predicates(): View { return view('assessments.predicates.index',['ranges'=>PredicateRange::orderBy('sequence')->get()]); }
    public function savePredicates(Request $request): RedirectResponse { foreach ($request->input('ranges',[]) as $id=>$data) { PredicateRange::find($id)?->update($data); } return back()->with('status','Rentang predikat diperbarui.'); }
}
