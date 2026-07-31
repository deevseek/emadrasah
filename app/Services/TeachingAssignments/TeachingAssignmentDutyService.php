<?php

declare(strict_types=1);
namespace App\Services\TeachingAssignments;
use App\Models\{AdditionalDuty,Classroom,Personnel,TeachingAssignmentSet,User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class TeachingAssignmentDutyService
{
 public const PERIODS=['headmaster'=>24,'homeroom_teacher'=>6,'operator'=>0,'administration_staff'=>0];
 public function save(User $actor,TeachingAssignmentSet $set,array $data,?AdditionalDuty $duty=null):AdditionalDuty{return DB::transaction(function()use($actor,$set,$data,$duty){if(!$set->isEditable())throw ValidationException::withMessages(['assignment_set_id'=>'Hanya draft yang dapat diubah.']);$personnel=Personnel::findOrFail($data['personnel_id']);if(!$personnel->is_active)throw ValidationException::withMessages(['personnel_id'=>'Personalia nonaktif tidak dapat diberi tugas.']);if(($data['duty_type']??'')==='homeroom_teacher'&&empty($data['classroom_id']))throw ValidationException::withMessages(['classroom_id'=>'Rombel wajib dipilih untuk tugas wali kelas.']);$periods=self::PERIODS[$data['duty_type']]??(int)($data['equivalent_periods']??0);$payload=[...$data,'assignment_set_id'=>$set->id,'academic_year_id'=>$set->academic_year_id,'equivalent_periods'=>$periods,'updated_by'=>$actor->id];if($duty){$duty->update($payload);$verb='Mengubah';}else{$duty=AdditionalDuty::firstOrCreate(['assignment_set_id'=>$set->id,'personnel_id'=>$personnel->id,'classroom_id'=>$data['classroom_id']??null,'duty_type'=>$data['duty_type']], [...$payload,'created_by'=>$actor->id]);$verb='Menambah';}activity('teaching-assignments')->causedBy($actor)->performedOn($duty)->log("{$verb} tugas tambahan {$duty->duty_name}.");return $duty;});}
}
