<?php

declare(strict_types=1);

namespace App\Http\Controllers\Btaq;

use App\Http\Controllers\Controller;
use App\Models\{BtaqGroup,BtaqGroupStudent,BtaqJournal,BtaqJournalStudent,BtaqMaterial,Student};
use App\Services\BtaqService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BtaqJournalController extends Controller
{
    public function dashboard(): View
    {
        return view('btaq.reports.dashboard', [
            'metrics' => [
                'today' => BtaqJournal::whereDate('journal_date', now())->count(),
                'draft' => BtaqJournal::where('status', 'draft')->count(),
                'pending' => BtaqJournal::where('status', 'submitted')->count(),
                'activeGroups' => BtaqGroup::where('is_active', true)->count(),
                'activeStudents' => Student::where('is_active', true)->count(),
                'guidance' => BtaqJournalStudent::where('progress_status', 'needs_guidance')->distinct('student_id')->count('student_id'),
            ],
            'latestProgress' => BtaqJournalStudent::latest()->limit(5)->get(),
        ]);
    }
    public function index(): View { return view('btaq.journals.index',['journals'=>BtaqJournal::latest()->paginate(15)]); }
    public function create(): View { return view('btaq.journals.form',['journal'=>new BtaqJournal,'groups'=>BtaqGroup::where('is_active',true)->get(),'materials'=>BtaqMaterial::where('is_active',true)->get(),'members'=>collect()]); }
    public function store(Request $request, BtaqService $service): RedirectResponse { $data=$this->validated($request); $students=$request->input('students',[]); $journal=$service->saveJournal($data,$students,auth()->id()); return redirect()->route('btaq-journals.show',$journal)->with('status','Jurnal disimpan.'); }
    public function show(BtaqJournal $btaqJournal): View { return view('btaq.journals.show',['journal'=>$btaqJournal,'details'=>BtaqJournalStudent::where('btaq_journal_id',$btaqJournal->id)->get()]); }
    public function edit(BtaqJournal $btaqJournal): View { abort_if($btaqJournal->status==='submitted',403); return view('btaq.journals.form',['journal'=>$btaqJournal,'groups'=>BtaqGroup::get(),'materials'=>BtaqMaterial::get(),'members'=>BtaqGroupStudent::where('btaq_group_id',$btaqJournal->btaq_group_id)->where('status','active')->get()]); }
    public function update(Request $request, BtaqJournal $btaqJournal, BtaqService $service): RedirectResponse { $service->saveJournal($this->validated($request),$request->input('students',[]),auth()->id(),$btaqJournal); return redirect()->route('btaq-journals.show',$btaqJournal)->with('status','Jurnal diperbarui.'); }
    public function submit(BtaqJournal $btaqJournal): RedirectResponse { abort_if(! in_array($btaqJournal->status, ['draft', 'rejected'], true), 422); $btaqJournal->update(['status'=>'submitted','submitted_at'=>now()]); activity('btaq')->performedOn($btaqJournal)->log('Jurnal BTAQ diajukan'); return back()->with('status','Jurnal diajukan.'); }
    public function verify(BtaqJournal $btaqJournal): RedirectResponse { abort_if($btaqJournal->status !== 'submitted', 422); $btaqJournal->update(['status'=>'verified','verified_at'=>now(),'verified_by'=>auth()->id()]); activity('btaq')->performedOn($btaqJournal)->log('Jurnal BTAQ diverifikasi'); return back()->with('status','Jurnal diverifikasi.'); }
    public function reject(Request $request, BtaqJournal $btaqJournal): RedirectResponse { abort_if($btaqJournal->status !== 'submitted', 422); $data=$request->validate(['rejection_reason'=>'required|string']); $btaqJournal->update(['status'=>'rejected','rejection_reason'=>$data['rejection_reason']]); activity('btaq')->performedOn($btaqJournal)->event('btaq.journal.rejected')->log('Jurnal BTAQ ditolak'); return back()->with('status','Jurnal ditolak.'); }
    public function recap(): View { return view('btaq.reports.recap',['groups'=>BtaqGroup::withCount('students')->get(),'guidance'=>BtaqJournalStudent::where('progress_status','needs_guidance')->latest()->paginate(20)]); }
    public function progress(): View { return view('btaq.reports.progress',['details'=>BtaqJournalStudent::latest()->paginate(20)]); }
    private function validated(Request $request): array { return $request->validate(['btaq_group_id'=>'required|exists:btaq_groups,id','journal_date'=>'required|date','session_number'=>'nullable|integer','starts_at'=>'nullable','ends_at'=>'nullable','btaq_material_id'=>'nullable|exists:btaq_materials,id','general_notes'=>'nullable','status'=>'nullable']); }
}
