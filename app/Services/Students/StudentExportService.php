<?php
declare(strict_types=1); namespace App\Services\Students;
use App\Models\{Student,User}; use App\Services\Personnel\SimpleXlsxService;
class StudentExportService
{
 public const HEADERS=['NO','NAMA LENGKAP','NISN','NIK','TEMPAT LAHIR','TANGGAL LAHIR','TINGKAT - ROMBEL','UMUR','STATUS','JENIS KELAMIN','ALAMAT','NO TELEPON','KEBUTUHAN KHUSUS','DISABILITAS','NOMOR KIP/PIP','NAMA AYAH KANDUNG','NAMA IBU KANDUNG','NAMA WALI'];
 public function __construct(private SimpleXlsxService $xlsx){}
 public function template():string{$path=tempnam(sys_get_temp_dir(),'students').'.xlsx';$this->xlsx->write([self::HEADERS],$path);return $path;}
 public function export($query,User $user):string{$full=$user->can('students.view-sensitive');$rows=[self::HEADERS];foreach($query->get() as $i=>$s)$rows[]=$this->row($s,$i+1,$full);$path=tempnam(sys_get_temp_dir(),'students').'.xlsx';$this->xlsx->write($rows,$path);activity('students')->causedBy($user)->log('Mengekspor data siswa ke XLSX.');return $path;}
 public function mask(?string $value,int $visible=4):string{if(blank($value))return '—';$length=mb_strlen($value);return str_repeat('*',max(6,$length-$visible)).mb_substr($value,-$visible);}
 private function row(Student $s,int $number,bool $full):array{return [$number,$s->full_name,$s->nisn,$full?$s->nik:$this->mask($s->nik),$s->birth_place,$s->birth_date?->format('Y-m-d'),$s->classroom_label,$s->age_label,$s->status_label,$s->gender_label,$full?$s->address:'Data disembunyikan',$full?$s->phone:$this->mask($s->phone),$s->special_needs,$s->disability,$full?$s->kip_pip_number:$this->mask($s->kip_pip_number),$s->father_name,$s->mother_name,$s->guardian_name];}
}
