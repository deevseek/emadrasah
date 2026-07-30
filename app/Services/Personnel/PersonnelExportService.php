<?php
declare(strict_types=1); namespace App\Services\Personnel;
use App\Models\Personnel; use App\Models\User;
class PersonnelExportService
{
 public const HEADERS=['NO','NAMA LENGKAP','L/P','TEMPAT, TGL. LAHIR','STATUS','NOMOR INDUK YAYASAN (NIY)','NIP','PANGKAT/GOLONGAN RUANG','Peg.ID','PENDIDIKAN TERAKHIR','JABATAN','SERTIFIKASI - IMPASSING','MAPEL SERTIFIKASI','JUMLAH JPL','JENIS REKENING','NO. REKENING','NO. HP/WA AKTIF','E-MAIL AKTIF'];
 public function __construct(private SimpleXlsxService $xlsx){}
 public function template():string{$path=tempnam(sys_get_temp_dir(),'personnel').'.xlsx';$this->xlsx->write([self::HEADERS],$path);return $path;}
 public function export($query,User $user):string{$sensitive=$user->can('personnel.view-sensitive');$rows=[self::HEADERS];foreach($query->get() as $i=>$p)$rows[]=$this->row($p,$i+1,$sensitive);$path=tempnam(sys_get_temp_dir(),'personnel').'.xlsx';$this->xlsx->write($rows,$path);activity('personnel')->causedBy($user)->log('Mengekspor data personalia ke XLSX.');return $path;}
 private function row(Personnel $p,int $n,bool $full):array{$mask=fn($v)=>$full?$v:$this->mask((string)$v);return [$n,$p->full_name,$p->gender_label,trim(($p->birth_place?:'').($p->birth_date?', '.$p->birth_date->format('d-m-Y'):''),', '),$p->employment_status,$p->foundation_employee_number,$mask($p->nip),$p->rank_grade,$mask($p->external_employee_id),$p->last_education,$p->position,$p->certification_status,$p->certification_subject,$p->weekly_teaching_hours,$mask($p->bank_name),$mask($p->bank_account_number),$mask($p->phone),$mask($p->email)];}
 public function mask(string $v):string{if($v==='')return '';if(str_contains($v,'@')){[$a,$b]=explode('@',$v,2);return substr($a,0,1).'******@'.$b;}return str_repeat('*',max(6,strlen($v)-4)).substr($v,-4);}
}
