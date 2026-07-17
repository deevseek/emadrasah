<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academic;

use App\Enums\SubjectCategory; use App\Http\Controllers\Controller; use App\Http\Requests\Academic\SubjectRequest; use App\Models\{GradeLevel, Subject}; use App\Services\ActivityLogger; use Illuminate\Http\{RedirectResponse, Request, StreamedResponse}; use Illuminate\View\View;

class SubjectController extends Controller
{
 public function __construct(private ActivityLogger $logger) {}
 public function index(Request $r): View { $subjects=Subject::with('gradeLevels')->when($r->search,fn($q,$v)=>$q->where(fn($x)=>$x->where('code','like',"%$v%")->orWhere('name','like',"%$v%")))->when($r->category,fn($q,$v)=>$q->where('category',$v))->when($r->filled('status'),fn($q)=>$q->where('is_active',$r->boolean('status')))->when($r->grade_level_id,fn($q,$v)=>$q->whereHas('gradeLevels',fn($g)=>$g->whereKey($v)))->orderBy('sort_order')->orderBy('name')->paginate(15)->withQueryString(); return view('subjects.index',['subjects'=>$subjects,'categories'=>SubjectCategory::cases(),'gradeLevels'=>GradeLevel::where('is_active',true)->get(),'filters'=>$r->all()]); }
 public function create(): View { return view('subjects.form',['subject'=>new Subject,'categories'=>SubjectCategory::cases(),'gradeLevels'=>GradeLevel::where('is_active',true)->get()]); }
 public function store(SubjectRequest $r): RedirectResponse { $d=$r->validated(); $levels=$d['grade_level_ids']??[]; unset($d['grade_level_ids']); $s=Subject::create($d+['is_active'=>$r->boolean('is_active',true),'sort_order'=>$d['sort_order']??0]); $s->gradeLevels()->sync($levels); $this->logger->log('subjects.create',$s,[],$s->getAttributes(),'Mata Pelajaran dibuat.'); return redirect()->route('subjects.show',$s)->with('status','Mata Pelajaran berhasil disimpan.'); }
 public function show(Subject $subject): View { return view('subjects.show',['subject'=>$subject->load('gradeLevels','teachingAssignments')]); }
 public function edit(Subject $subject): View { return view('subjects.form',['subject'=>$subject->load('gradeLevels'),'categories'=>SubjectCategory::cases(),'gradeLevels'=>GradeLevel::where('is_active',true)->get()]); }
 public function update(SubjectRequest $r, Subject $subject): RedirectResponse { $old=$subject->getAttributes(); $d=$r->validated(); $levels=$d['grade_level_ids']??[]; unset($d['grade_level_ids']); $subject->update($d+['is_active'=>$r->boolean('is_active')]); $subject->gradeLevels()->sync($levels); $this->logger->log('subjects.update',$subject,$old,$subject->getAttributes(),'Mata Pelajaran diperbarui.'); return redirect()->route('subjects.show',$subject)->with('status','Mata Pelajaran berhasil diperbarui.'); }
 public function toggle(Subject $subject): RedirectResponse { $old=$subject->getAttributes(); $subject->update(['is_active'=>!$subject->is_active]); $this->logger->log('subjects.status-changed',$subject,$old,$subject->getAttributes(),'Status Mata Pelajaran diubah.'); return back()->with('status','Status Mata Pelajaran diperbarui.'); }
 public function destroy(Subject $subject): RedirectResponse { if($subject->teachingAssignments()->exists()||$subject->schedules()->exists()){ if($subject->is_active) $subject->update(['is_active'=>false]); return back()->with('status','Mata Pelajaran sudah digunakan sehingga dinonaktifkan, bukan dihapus.'); } $subject->delete(); return redirect()->route('subjects.index')->with('status','Mata Pelajaran dihapus.'); }
 public function export(Request $r): StreamedResponse { $rows=Subject::with('gradeLevels')->get(); return response()->streamDownload(function()use($rows){$f=fopen('php://output','w'); fputcsv($f,['Kode','Nama','Kelompok','Tingkat','Status']); foreach($rows as $s) fputcsv($f,[$s->code,$s->name,$s->category?->label(),$s->gradeLevels->pluck('name')->join(', '),$s->is_active?'Aktif':'Nonaktif']);},'mata-pelajaran.csv',['Content-Type'=>'text/csv']);}
}
