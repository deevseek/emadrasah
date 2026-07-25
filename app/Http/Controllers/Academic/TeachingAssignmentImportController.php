<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\{ImportPreviewRequest,ImportProcessRequest};
use App\Models\{AcademicYear,ImportBatch,Semester};
use App\Services\Academic\Imports\{SimpleXlsx,TeachingAssignmentImportService};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\View\View;

class TeachingAssignmentImportController extends Controller
{
    private const HEADERS=['tahun_ajaran','semester','nama_guru','nomor_pegawai','kelas','kode_kelas','mata_pelajaran','kode_mata_pelajaran','jp_per_minggu','tanggal_mulai','tanggal_selesai','keterangan','aktif'];
    public function index():View{return view('imports.form',['kind'=>'teaching','title'=>'Impor Penugasan Mengajar','academicYears'=>AcademicYear::with('semesters')->get(),'batches'=>ImportBatch::with('importer')->where('type','teaching_assignment')->latest()->take(20)->get()]);}
    public function template(SimpleXlsx$xlsx){return $xlsx->download(self::HEADERS,[['2026/2027','Ganjil','Nama Guru','PEG-001',"I As-Salam (Fullday)",'I-AS','Matematika','MTK','4','2026-07-01','2026-12-31','','1']],'template-penugasan-mengajar.xlsx');}
    public function preview(ImportPreviewRequest$request,TeachingAssignmentImportService$service):View{$file=$request->file('file');$path=$file->store('academic-imports','local');$preview=$service->preview(storage_path('app/private/'.$path),(int)$request->academic_year_id,(int)$request->semester_id);$token=(string)str()->uuid();session()->put('import_preview.'.$token,$preview+['year'=>(int)$request->academic_year_id,'semester'=>(int)$request->semester_id,'mode'=>$request->mode??'create','filename'=>$file->getClientOriginalName()]);return view('imports.preview',['kind'=>'teaching','title'=>'Preview Penugasan Mengajar','preview'=>$preview,'token'=>$token]);}
    public function process(ImportProcessRequest$request,TeachingAssignmentImportService$service):RedirectResponse{$data=session()->pull('import_preview.'.$request->preview_token);abort_unless($data,419);if($data['mode']==='replace'&&!$request->boolean('confirm_replace'))return back()->withErrors(['confirm_replace'=>'Konfirmasi penonaktifan wajib diberikan.']);$batch=$service->process($data['rows'],$data['year'],$data['semester'],$data['mode'],$data['filename']);return redirect()->route('teaching-assignments.import')->with('status',"Impor selesai: {$batch->imported_rows} data disimpan.");}
}
