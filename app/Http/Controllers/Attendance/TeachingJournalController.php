<?php

declare(strict_types=1);

namespace App\Http\Controllers\Attendance;

use App\Enums\TeachingJournalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\TeachingJournalRejectRequest;
use App\Http\Requests\Attendance\TeachingJournalStoreRequest;
use App\Http\Requests\Attendance\TeachingJournalUpdateRequest;
use App\Models\LessonSchedule;
use App\Models\TeachingJournal;
use App\Services\Attendance\TeachingJournalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

final class TeachingJournalController extends Controller
{
    public function index(Request $request, TeachingJournalService $service): View
    {
        $user = $request->user(); $employeeId = $user->employee?->id;
        $journals = TeachingJournal::with('employee','classroom','subject','lessonSchedule')->when(! $user->can('teaching-journals.view'), fn ($q) => $q->where('employee_id',$employeeId))
            ->when($request->filled('status'), fn ($q) => $q->where('status',$request->status))->when($request->filled('date'), fn ($q) => $q->whereDate('journal_date',$request->date))->latest('journal_date')->paginate(15)->withQueryString();
        $todaySchedules = LessonSchedule::with('classroom','subject','teachingAssignment.employee')->where('is_active',true)->when(! $user->can('teaching-journals.view'), fn ($q) => $q->where('employee_id',$employeeId))->where('day_of_week', $this->dayValue(today()->dayOfWeekIso))->orderBy('starts_at')->get();
        $todayJournals = TeachingJournal::whereDate('journal_date', today())->whereIn('lesson_schedule_id', $todaySchedules->pluck('id'))->get()->keyBy('lesson_schedule_id');
        return view('attendance.journals.index', compact('journals','todaySchedules','todayJournals'));
    }
    public function create(Request $request, TeachingJournalService $service): View
    {
        $schedule = LessonSchedule::with('employee','classroom','subject','academicYear','semester','teachingAssignment')->findOrFail($request->integer('lesson_schedule_id'));
        $this->authorizeSchedule($schedule);
        return view('attendance.journals.form', ['journal'=>null,'schedule'=>$schedule,'meetingNumber'=>$service->nextMeetingNumber((int)$schedule->teaching_assignment_id),'date'=>$request->input('date', today()->toDateString())]);
    }
    public function store(TeachingJournalStoreRequest $request, TeachingJournalService $service): RedirectResponse
    { $journal = $service->createFromSchedule(LessonSchedule::with('teachingAssignment')->findOrFail($request->integer('lesson_schedule_id')), $request->validated()); return redirect()->route('teaching-journals.show',$journal)->with('status','Jurnal tersimpan.'); }
    public function edit(TeachingJournal $teachingJournal): View
    { $this->authorizeOwnership($teachingJournal); abort_unless($teachingJournal->isEditableByTeacher(), 422); return view('attendance.journals.form', ['journal'=>$teachingJournal->load('lessonSchedule.employee','lessonSchedule.classroom','lessonSchedule.subject','lessonSchedule.academicYear','lessonSchedule.semester'),'schedule'=>$teachingJournal->lessonSchedule,'meetingNumber'=>$teachingJournal->meeting_number,'date'=>$teachingJournal->journal_date->toDateString()]); }
    public function update(TeachingJournalUpdateRequest $request, TeachingJournal $teachingJournal, TeachingJournalService $service): RedirectResponse
    { $this->authorizeOwnership($teachingJournal); $journal = $service->update($teachingJournal, $request->validated()); return redirect()->route('teaching-journals.show',$journal)->with('status','Jurnal diperbarui.'); }
    public function show(TeachingJournal $teachingJournal): View
    { $this->authorizeOwnership($teachingJournal); return view('attendance.journals.show', ['journal'=>$teachingJournal->load('employee','classroom','subject','academicYear','semester','lessonSchedule','verifier','rejector')]); }
    public function print(TeachingJournal $teachingJournal): View
    { $this->authorizePrint($teachingJournal); return view('attendance.journals.print', ['journal'=>$teachingJournal->load('employee','classroom','subject','academicYear','semester','verifier')]); }
    public function submit(TeachingJournal $teachingJournal, TeachingJournalService $service): RedirectResponse
    { $this->authorizeOwnership($teachingJournal); $service->submit($teachingJournal); return back()->with('status','Jurnal dikirim.'); }
    public function verify(TeachingJournal $teachingJournal, TeachingJournalService $service): RedirectResponse
    { $service->verify($teachingJournal, request('verification_notes')); return back()->with('status','Jurnal diverifikasi.'); }
    public function reject(TeachingJournalRejectRequest $request, TeachingJournal $teachingJournal, TeachingJournalService $service): RedirectResponse
    { $service->reject($teachingJournal, $request->validated('rejection_reason')); return back()->with('status','Jurnal dikembalikan untuk diperbaiki.'); }
    public function export(Request $request)
    { abort_unless($request->user()->can('teaching-journals.export'),403); $rows = TeachingJournal::with('employee','classroom','subject')->get(); $csv="tanggal,guru,kelas,mapel,status\n"; foreach($rows as $j){$csv.=$j->journal_date->toDateString().",{$j->employee->name},{$j->classroom->name},{$j->subject->name},{$j->status->value}\n";} return Response::make($csv,200,['Content-Type'=>'text/csv','Content-Disposition'=>'attachment; filename=jurnal-mengajar.csv']); }
    private function authorizeSchedule(LessonSchedule $schedule): void { abort_if(auth()->user()?->employee && auth()->user()->employee->id !== $schedule->employee_id, 403); }
    private function authorizeOwnership(TeachingJournal $journal): void { abort_if(! auth()->user()->can('teaching-journals.view') && $journal->employee_id !== auth()->user()->employee?->id, 403); }
    private function authorizePrint(TeachingJournal $journal): void { abort_if(! auth()->user()->can('teaching-journals.print') && $journal->employee_id !== auth()->user()->employee?->id, 403); }
    private function dayValue(int $iso): string { return [1=>'senin',2=>'selasa',3=>'rabu',4=>'kamis',5=>'jumat',6=>'sabtu',7=>'ahad'][$iso] ?? 'senin'; }
}
