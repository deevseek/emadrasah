<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pmbm;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Pmbm\PmbmApplicant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\{Request, StreamedResponse};
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View { return view('pmbm.admin.report',['applicants'=>$this->query($request)->paginate(30)->withQueryString(),'years'=>AcademicYear::latest('starts_at')->get()]); }
    public function export(Request $request): StreamedResponse
    {
        return response()->streamDownload(function() use($request): void { $out=fopen('php://output','w'); fputcsv($out,['Nomor','Nama','Tahun ajaran','Tanggal','Sumber','Status','Gender']); $this->query($request)->with('academicYear')->each(fn($a)=>fputcsv($out,[$a->registration_number,$a->full_name,$a->academicYear?->name,$a->submitted_at?->format('Y-m-d'),$a->registration_source,$a->status->label(),$a->gender])); fclose($out); },'laporan-pmbm-'.now()->format('Ymd-His').'.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }
    private function query(Request $request): Builder
    {
        return PmbmApplicant::query()->with('academicYear')->when($request->integer('academic_year_id'),fn($q,$v)=>$q->where('academic_year_id',$v))->when($request->input('date_from'),fn($q,$v)=>$q->whereDate('submitted_at','>=',$v))->when($request->input('date_to'),fn($q,$v)=>$q->whereDate('submitted_at','<=',$v))->when($request->input('source'),fn($q,$v)=>$q->where('registration_source',$v))->when($request->input('status'),fn($q,$v)=>$q->where('status',$v))->when($request->input('gender'),fn($q,$v)=>$q->where('gender',$v))->latest();
    }
}
