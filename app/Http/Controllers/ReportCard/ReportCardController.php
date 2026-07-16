<?php

declare(strict_types=1);

namespace App\Http\Controllers\ReportCard;

use App\Http\Controllers\Controller;
use App\Models\{Classroom,ReportCard,ReportCardStatusHistory,ReportCardSubject,StudentAchievement,StudentAttitudeNote,StudentEnrollment,StudentExtracurricular};
use App\Services\ReportCardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportCardController extends Controller
{
    public function dashboard(): View
    {
        return view('report-cards.dashboard', [
            'metrics' => [
                'draft' => ReportCard::where('status', 'draft')->count(),
                'submitted' => ReportCard::where('status', 'submitted')->count(),
                'approved' => ReportCard::where('status', 'approved')->count(),
                'locked' => ReportCard::where('status', 'locked')->count(),
                'gradeCompleteness' => ReportCardSubject::count(),
                'classesWithoutReports' => Classroom::whereNotIn('id', ReportCard::select('classroom_id'))->count(),
            ],
            'latestCards' => ReportCard::with(['student', 'classroom'])->latest()->limit(5)->get(),
        ]);
    }
    public function classes(): View { return view('report-cards.classes',['classes'=>Classroom::withCount('studentEnrollments')->paginate(15)]); }
    public function students(Classroom $classroom): View { return view('report-cards.students',['classroom'=>$classroom,'enrollments'=>StudentEnrollment::where('classroom_id',$classroom->id)->where('enrollment_status','active')->with('student')->paginate(30)]); }
    public function generate(Request $request, StudentEnrollment $enrollment, ReportCardService $service): RedirectResponse { $card=$service->generate($enrollment,(int)$request->validate(['semester_id'=>'required|exists:semesters,id'])['semester_id']); return redirect()->route('report-cards.show',$card)->with('status','Draft rapor digenerate.'); }
    public function show(ReportCard $reportCard): View { $reportCard->load(['student','classroom','subjects']); return view('report-cards.show',['card'=>$reportCard,'histories'=>ReportCardStatusHistory::where('report_card_id',$reportCard->id)->get(),'attitude'=>StudentAttitudeNote::where('student_id',$reportCard->student_id)->where('semester_id',$reportCard->semester_id)->first(),'extras'=>StudentExtracurricular::where('student_id',$reportCard->student_id)->where('semester_id',$reportCard->semester_id)->get(),'achievements'=>StudentAchievement::where('student_id',$reportCard->student_id)->where(function($q) use ($reportCard){$q->whereNull('semester_id')->orWhere('semester_id',$reportCard->semester_id);})->get()]); }
    public function update(Request $request, ReportCard $reportCard): RedirectResponse { abort_if($reportCard->status==='locked',403); $reportCard->update($request->validate(['homeroom_notes'=>'nullable','general_notes'=>'nullable','place'=>'nullable','report_date'=>'nullable|date'])); return back()->with('status','Catatan rapor disimpan.'); }
    public function submit(ReportCard $reportCard, ReportCardService $service): RedirectResponse { abort_if($reportCard->subjects()->count()===0,422,'Nilai wajib belum lengkap.'); $service->transition($reportCard,'submitted'); return back()->with('status','Rapor diajukan.'); }
    public function approve(ReportCard $reportCard, ReportCardService $service): RedirectResponse { $service->transition($reportCard,'approved'); return back()->with('status','Rapor disetujui.'); }
    public function lock(ReportCard $reportCard, ReportCardService $service): RedirectResponse { $service->transition($reportCard,'locked'); return back()->with('status','Rapor dikunci.'); }
    public function reopen(Request $request, ReportCard $reportCard, ReportCardService $service): RedirectResponse { $service->transition($reportCard,'reopened',$request->validate(['reason'=>'required|string'])['reason']); return back()->with('status','Rapor dibuka kembali.'); }
    public function print(ReportCard $reportCard): View { $reportCard->load(['student','classroom','subjects']); return view('report-cards.print',['card'=>$reportCard]); }
    public function verification(): View { return view('report-cards.verification',['cards'=>ReportCard::with(['student','classroom'])->where('status','submitted')->paginate(20)]); }
}
