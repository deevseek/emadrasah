<?php

declare(strict_types=1);

namespace App\Http\Controllers\Attendance;

use App\Enums\TeachingJournalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\TeachingJournalRejectRequest;
use App\Http\Requests\Attendance\TeachingJournalStoreRequest;
use App\Http\Requests\Attendance\TeachingJournalTemplateUploadRequest;
use App\Http\Requests\Attendance\TeachingJournalUpdateRequest;
use App\Models\LessonSchedule;
use App\Models\TeachingJournal;
use App\Services\Attendance\TeachingJournalService;
use App\Services\Attendance\TeachingJournalTemplateService;
use App\Services\Foundation\SchoolProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

final class TeachingJournalController extends Controller
{
    public function index(Request $request, TeachingJournalService $service, TeachingJournalTemplateService $templates): View
    {
        $user = $request->user(); $employeeId = $user->employee?->id;
        $journals = TeachingJournal::with('employee','classroom','subject','lessonSchedule')->when(! $user->can('teaching-journals.view'), fn ($q) => $q->where('employee_id',$employeeId))
            ->when($request->filled('status'), fn ($q) => $q->where('status',$request->status))->when($request->filled('date'), fn ($q) => $q->whereDate('journal_date',$request->date))->latest('journal_date')->paginate(15)->withQueryString();
        $todaySchedules = LessonSchedule::with('classroom','subject','teachingAssignment.employee')->where('is_active',true)->when(! $user->can('teaching-journals.view'), fn ($q) => $q->where('employee_id',$employeeId))->where('day_of_week', $this->dayValue(today()->dayOfWeekIso))->orderBy('starts_at')->get();
        $todayJournals = TeachingJournal::whereDate('journal_date', today())->whereIn('lesson_schedule_id', $todaySchedules->pluck('id'))->get()->keyBy('lesson_schedule_id');
        return view('attendance.journals.index', compact('journals','todaySchedules','todayJournals') + ['templatePaths' => ['teacher' => $templates->templatePath('teacher'), 'class' => $templates->templatePath('class')]]);
    }
    public function create(Request $request, TeachingJournalService $service): View
    {
        $schedule = LessonSchedule::with('employee','classroom','subject','academicYear','semester','teachingAssignment')->findOrFail($request->integer('lesson_schedule_id'));
        abort_if(! $schedule->employee_id || ! $schedule->teaching_assignment_id, 422, 'Guru pengampu belum ditetapkan pada jadwal ini. Tetapkan penugasan mengajar terlebih dahulu sebelum mengisi jurnal.');
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

    public function printMonthly(Request $request, SchoolProfileService $profiles, TeachingJournalTemplateService $templates)
    {
        $validated = $request->validate([
            'type' => ['nullable', 'in:teacher,class'],
            'month' => ['nullable', 'date_format:Y-m'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
        ]);

        $type = $validated['type'] ?? 'teacher';
        $month = now()->parse(($validated['month'] ?? today()->format('Y-m')).'-01');
        $journals = $this->monthlyJournals($request, $validated, $type, $month);
        $path = $templates->render($type, $journals, $profiles->current(), $month);
        $filename = 'preview-jurnal-'.($type === 'class' ? 'kelas' : 'guru').'-'.$month->format('Y-m').'.docx';

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    public function uploadTemplate(TeachingJournalTemplateUploadRequest $request, TeachingJournalTemplateService $templates): RedirectResponse
    {
        $templates->storeTemplate($request->validated('type'), $request->file('template'));

        return back()->with('status', 'Template Word jurnal berhasil diunggah.');
    }

    public function exportTemplate(Request $request, SchoolProfileService $profiles, TeachingJournalTemplateService $templates)
    {
        abort_unless($request->user()->can('teaching-journals.print') || $request->user()->can('teaching-journals.print-own'), 403);

        $validated = $request->validate([
            'type' => ['required', 'in:teacher,class'],
            'month' => ['nullable', 'date_format:Y-m'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
        ]);

        $type = $validated['type'];
        $month = now()->parse(($validated['month'] ?? today()->format('Y-m')).'-01');
        $journals = $this->monthlyJournals($request, $validated, $type, $month);
        $path = $templates->render($type, $journals, $profiles->current(), $month);
        $filename = 'jurnal-'.($type === 'class' ? 'kelas' : 'guru').'-'.$month->format('Y-m').'.docx';

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    private function monthlyJournals(Request $request, array $validated, string $type, \Illuminate\Support\Carbon $month)
    {
        $user = $request->user();
        $employeeId = $user->employee?->id;

        return TeachingJournal::with('employee','classroom.homeroomTeacher','subject','academicYear','semester')
            ->when(! $user->can('teaching-journals.print'), fn ($q) => $q->where('employee_id', $employeeId))
            ->when($type === 'teacher' && ! empty($validated['employee_id']) && $user->can('teaching-journals.print'), fn ($q) => $q->where('employee_id', $validated['employee_id']))
            ->when($type === 'class' && ! empty($validated['classroom_id']), fn ($q) => $q->where('classroom_id', $validated['classroom_id']))
            ->whereBetween('journal_date', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->orderBy('journal_date')
            ->orderBy('scheduled_start_time')
            ->get();
    }

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
