<?php
declare(strict_types=1);
namespace App\Http\Controllers\Academic;
use App\Http\Controllers\Controller;use App\Http\Requests\Academic\AcademicSubjectRequest;use App\Models\AcademicSubject;use App\Services\Academic\AcademicSubjectService;use Illuminate\Http\{RedirectResponse,Request};use Illuminate\View\View;
class AcademicSubjectController extends Controller
{
 public function index(Request $r):View{$q=AcademicSubject::query()->when($r->filled('q'),fn($x)=>$x->where(fn($y)=>$y->where('name','like','%'.$r->q.'%')->orWhere('code','like','%'.$r->q.'%')));return view('academic.subjects.index',['subjects'=>$q->orderBy('sort_order')->orderBy('name')->paginate(20)->withQueryString(),'stats'=>['total'=>AcademicSubject::count(),'active'=>AcademicSubject::where('is_active',true)->count(),'inactive'=>AcademicSubject::where('is_active',false)->count()]]);}
 public function store(AcademicSubjectRequest $r,AcademicSubjectService $s):RedirectResponse{$s->create($r->validated(),$r->user());return back()->with('success','Mata pelajaran berhasil ditambahkan.');}
 public function update(AcademicSubjectRequest $r,AcademicSubject $subject,AcademicSubjectService $s):RedirectResponse{$s->update($subject,$r->validated(),$r->user());return back()->with('success','Mata pelajaran berhasil diperbarui.');}
}
