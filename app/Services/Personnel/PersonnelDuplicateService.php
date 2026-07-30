<?php
declare(strict_types=1); namespace App\Services\Personnel;
use App\Models\Personnel;
class PersonnelDuplicateService
{
 public function find(array $data):array{$matches=collect();foreach(['foundation_employee_number','nip','external_employee_id','email'] as $key)if(!empty($data[$key]))$matches->push(...Personnel::where($key,$data[$key])->get());if(!empty($data['full_name'])&&!empty($data['birth_date']))$matches->push(...Personnel::where('full_name',$data['full_name'])->whereDate('birth_date',$data['birth_date'])->get());$unique=$matches->unique('id');return ['match'=>$unique->count()===1?$unique->first():null,'conflict'=>$unique->count()>1];}
}
