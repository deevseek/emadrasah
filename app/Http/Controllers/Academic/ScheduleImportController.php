<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;use App\Http\Requests\Academic\{ImportPreviewRequest,ImportProcessRequest};use App\Models\{AcademicYear,ImportBatch};use App\Services\Academic\Imports\{LessonScheduleImportService,SimpleXlsx};use Illuminate\Http\RedirectResponse;use Illuminate\View\View;
class ScheduleImportController extends Controller
{
 private const HEADERS=['tahun_ajaran','semester','kelas','kode_kelas','hari','waktu_mulai','waktu_selesai','jenis_slot','mata_pelajaran','kode_mata_pelajaran','nama_kegiatan','guru','nomor_pegawai','jp','ruangan','keterangan'];
 public function index():View{return view('imports.form',['kind'=>'schedule','title'=>'Impor Jadwal Pelajaran','academicYears'=>AcademicYear::with('semesters')->get(),'batches'=>ImportBatch::with('importer')->where('type','lesson_schedule')->latest()->take(20)->get()]);}
 public function template(SimpleXlsx$xlsx){return $xlsx->download(self::HEADERS,[['2026/2027','Ganjil','I Ar-Rahman','','senin','06:50','07:25','lesson','Matematika','','','Nama Guru','','1','I Ar-Rahman',''],['2026/2027','Ganjil','I Ar-Rahman','','senin','09:00','09:15','break','','','Istirahat','','','1','','']], 'template-jadwal-pelajaran.xlsx');}
 public function preview(ImportPreviewRequest$r,LessonScheduleImportService$s):View{$f=$r->file('file');$p=$f->store('academic-imports','local');$preview=$s->preview(storage_path('app/private/'.$p),(int)$r->academic_year_id,(int)$r->semester_id);$token=(string)str()->uuid();session()->put('import_preview.'.$token,$preview+['year'=>(int)$r->academic_year_id,'semester'=>(int)$r->semester_id,'filename'=>$f->getClientOriginalName()]);return view('imports.preview',['kind'=>'schedule','title'=>'Preview Jadwal Pelajaran','preview'=>$preview,'token'=>$token]);}
 public function process(ImportProcessRequest$r,LessonScheduleImportService$s):RedirectResponse{$d=session()->pull('import_preview.'.$r->preview_token);abort_unless($d,419);$b=$s->process($d['rows'],$d['year'],$d['semester'],$d['filename']);return redirect()->route('schedules.import')->with('status',"Impor selesai: {$b->imported_rows} slot disimpan.");}
}
