<?php

declare(strict_types=1);

namespace App\Http\Controllers\Btaq;

use App\Http\Controllers\Controller;
use App\Models\{AcademicYear,BtaqGroup,BtaqGroupStudent,BtaqLevel,Employee,Semester,Student};
use App\Services\BtaqService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BtaqGroupController extends Controller
{
    public function index(): View { $groups = BtaqGroup::with(['employee','level'])->latest()->paginate(15); return view('btaq.groups.index', compact('groups')); }
    public function create(): View { return view('btaq.groups.form', $this->options() + ['group'=>new BtaqGroup]); }
    public function store(Request $request): RedirectResponse { BtaqGroup::create($this->validateGroup($request)); return redirect()->route('btaq-groups.index')->with('status','Kelompok BTAQ disimpan.'); }
    public function show(BtaqGroup $btaqGroup): View { return view('btaq.groups.show',['group'=>$btaqGroup,'members'=>BtaqGroupStudent::with('student')->where('btaq_group_id',$btaqGroup->id)->paginate(20),'students'=>Student::where('is_active',true)->orderBy('name')->get(),'groups'=>BtaqGroup::where('semester_id',$btaqGroup->semester_id)->whereKeyNot($btaqGroup->id)->get()]); }
    public function edit(BtaqGroup $btaqGroup): View { return view('btaq.groups.form', $this->options() + ['group'=>$btaqGroup]); }
    public function update(Request $request, BtaqGroup $btaqGroup): RedirectResponse { $btaqGroup->update($this->validateGroup($request, $btaqGroup->id)); return redirect()->route('btaq-groups.show',$btaqGroup)->with('status','Kelompok diperbarui.'); }
    public function addMembers(Request $request, BtaqGroup $btaqGroup, BtaqService $service): RedirectResponse { $service->addMembers($btaqGroup, $request->validate(['student_ids'=>'required|array','student_ids.*'=>'exists:students,id'])['student_ids'], auth()->id()); return back()->with('status','Anggota ditambahkan.'); }
    public function complete(BtaqGroupStudent $member): RedirectResponse { $member->update(['status'=>'completed','completed_at'=>now()->toDateString()]); activity('btaq')->performedOn($member)->log('Anggota BTAQ selesai'); return back()->with('status','Anggota ditandai selesai.'); }
    public function transfer(Request $request, BtaqGroupStudent $member, BtaqService $service): RedirectResponse { $target = BtaqGroup::findOrFail($request->validate(['target_group_id'=>'required|exists:btaq_groups,id'])['target_group_id']); DB::transaction(function () use ($member,$target,$service): void { $member->update(['status'=>'transferred','completed_at'=>now()->toDateString()]); $service->addMembers($target, [$member->student_id], auth()->id()); }); return back()->with('status','Anggota ditransfer.'); }
    private function options(): array { return ['years'=>AcademicYear::get(),'semesters'=>Semester::get(),'levels'=>BtaqLevel::get(),'employees'=>Employee::where('is_active',true)->get()]; }
    private function validateGroup(Request $request, ?int $id = null): array { return $request->validate(['academic_year_id'=>'required|exists:academic_years,id','semester_id'=>'required|exists:semesters,id','name'=>'required','code'=>'required|unique:btaq_groups,code,'.($id ?? 'NULL').',id,academic_year_id,'.$request->academic_year_id,'employee_id'=>'required|exists:employees,id','btaq_level_id'=>'required|exists:btaq_levels,id','capacity'=>'nullable|integer|min:1','is_active'=>'boolean','notes'=>'nullable']); }
}
