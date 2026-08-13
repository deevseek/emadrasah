<?php
declare(strict_types=1);
namespace App\Services\Academic;
use App\Models\{AcademicSubject,User};use Illuminate\Support\Facades\DB;
class AcademicSubjectService
{
 public function create(array $data,User $user):AcademicSubject{return DB::transaction(function()use($data,$user){$subject=AcademicSubject::create($data+['created_by'=>$user->id]);activity('akademik')->causedBy($user)->performedOn($subject)->withProperties(['kode'=>$subject->code])->log('Menambah Mata Pelajaran');return $subject;});}
 public function update(AcademicSubject $subject,array $data,User $user):AcademicSubject{return DB::transaction(function()use($subject,$data,$user){$was=$subject->is_active;$subject->update($data+['updated_by'=>$user->id]);$message=$was&&!$subject->is_active?'Menonaktifkan Mata Pelajaran':'Mengubah Mata Pelajaran';activity('akademik')->causedBy($user)->performedOn($subject)->withProperties(['kode'=>$subject->code])->log($message);return $subject;});}
}
