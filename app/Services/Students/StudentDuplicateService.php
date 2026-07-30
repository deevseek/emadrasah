<?php
declare(strict_types=1); namespace App\Services\Students;
use App\Models\Student;
class StudentDuplicateService
{
 public function find(array $data):array{$nisn=filled($data['nisn']??null)?Student::where('nisn',$data['nisn'])->first():null;$nik=filled($data['nik']??null)?Student::where('nik',$data['nik'])->first():null;if($nisn&&$nik&&!$nisn->is($nik))return ['match'=>null,'matched_by'=>null,'conflict'=>true,'message'=>'NISN dan NIK mengarah pada data siswa yang berbeda.'];if($nisn)return ['match'=>$nisn,'matched_by'=>'nisn','conflict'=>false];if($nik)return ['match'=>$nik,'matched_by'=>'nik','conflict'=>false];$match=null;if(filled($data['full_name']??null)&&filled($data['birth_date']??null))$match=Student::whereRaw('LOWER(full_name) = ?', [mb_strtolower(trim($data['full_name']))])->whereDate('birth_date',$data['birth_date'])->first();return ['match'=>$match,'matched_by'=>$match?'name_and_birth_date':null,'conflict'=>false];}
}
