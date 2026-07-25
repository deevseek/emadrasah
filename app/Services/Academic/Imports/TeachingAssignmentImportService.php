<?php

declare(strict_types=1);

namespace App\Services\Academic\Imports;

use App\Models\{ImportBatch,Semester,TeachingAssignment};
use App\Services\Academic\TeachingAssignmentService;
use Illuminate\Support\Facades\{Auth,DB};

final class TeachingAssignmentImportService
{
    public function __construct(private SimpleXlsx $xlsx, private ImportMatcher $matcher, private TeachingAssignmentService $assignments) {}
    public function preview(string $path, int $year, int $semester): array
    {
        $rows=$this->xlsx->read($path);$seen=[];$result=[];
        foreach($rows as $i=>$row){$status='valid_new';$messages=[];$employee=$this->matcher->employee($row['nomor_pegawai']??null,$row['nama_guru']??null);$class=$this->matcher->classroom($year,$row['kode_kelas']??null,$row['kelas']??null);$subject=['model'=>null,'status'=>'subject_not_found'];if($class['model'])$subject=$this->matcher->subject($row['kode_mata_pelajaran']??null,$row['mata_pelajaran']??null,$class['model']->grade_level_id);foreach([$employee,$class,$subject]as$m)if($m['status']){$status=$m['status'];$messages[]=$m['status'];}
            $hours=filter_var($row['jp_per_minggu']??null,FILTER_VALIDATE_INT);if($hours===false||$hours<1||$hours>60){$status='invalid_weekly_hours';$messages[]='JP per minggu harus 1 sampai 60.';}
            $key=implode(':',[$class['model']?->id,$subject['model']?->id]);if(isset($seen[$key])&&$class['model']&&$subject['model']){$status='duplicate_in_file';$messages[]='Kombinasi kelas dan mata pelajaran berulang dalam file.';}$seen[$key]=true;
            $existing=null;if($class['model']&&$subject['model'])$existing=TeachingAssignment::where(['academic_year_id'=>$year,'semester_id'=>$semester,'classroom_id'=>$class['model']->id,'subject_id'=>$subject['model']->id,'is_active'=>true])->first();if($status==='valid_new'&&$existing)$status=(int)$existing->employee_id===(int)$employee['model']?->id&&$existing->weekly_hours===$hours?'unchanged':'valid_update';
            $result[]=['line'=>$i+2,'source'=>$row,'status'=>$status,'messages'=>$messages,'employee_id'=>$employee['model']?->id,'classroom_id'=>$class['model']?->id,'subject_id'=>$subject['model']?->id,'existing_id'=>$existing?->id,'weekly_hours'=>$hours?:null];
        }return ['rows'=>$result,'summary'=>$this->summary($result)];
    }
    public function process(array $preview,int $year,int $semester,string $mode,string $filename): ImportBatch
    {
        return DB::transaction(function()use($preview,$year,$semester,$mode,$filename){$batch=ImportBatch::create(['type'=>'teaching_assignment','original_filename'=>$filename,'academic_year_id'=>$year,'semester_id'=>$semester,'status'=>'processing','total_rows'=>count($preview),'valid_rows'=>collect($preview)->whereIn('status',['valid_new','valid_update','unchanged'])->count(),'imported_by'=>Auth::id(),'started_at'=>now()]);$imported=0;$skipped=0;$touched=[];
            foreach($preview as$row){if(!in_array($row['status'],['valid_new','valid_update'],true)||($row['status']==='valid_update'&&$mode==='create')){$skipped++;continue;}$payload=['academic_year_id'=>$year,'semester_id'=>$semester,'employee_id'=>$row['employee_id'],'classroom_id'=>$row['classroom_id'],'subject_id'=>$row['subject_id'],'weekly_hours'=>$row['weekly_hours'],'starts_on'=>$row['source']['tanggal_mulai']?:null,'ends_on'=>$row['source']['tanggal_selesai']?:null,'notes'=>$row['source']['keterangan']?:null,'is_active'=>$this->boolean($row['source']['aktif']??'1'),'import_batch_id'=>$batch->id,'source_reference'=>$filename.':'.$row['line']];$existing=$row['existing_id']?TeachingAssignment::lockForUpdate()->find($row['existing_id']):null;$saved=$this->assignments->save($payload,$existing);$touched[]=$saved->id;$imported++;}
            if($mode==='replace')TeachingAssignment::where(['academic_year_id'=>$year,'semester_id'=>$semester,'is_active'=>true])->whereNotIn('id',$touched)->update(['is_active'=>false]);$batch->update(['status'=>'completed','imported_rows'=>$imported,'skipped_rows'=>$skipped,'error_rows'=>count($preview)-$imported-$skipped,'finished_at'=>now()]);return$batch;});
    }
    private function boolean(string $v): bool{return !in_array(mb_strtolower(trim($v)),['0','tidak','false','nonaktif'],true);}
    private function summary(array $rows): array {$c=array_count_values(array_column($rows,'status'));return ['total'=>count($rows),'valid'=>collect($rows)->whereIn('status',['valid_new','valid_update','unchanged'])->count()]+$c;}
}
