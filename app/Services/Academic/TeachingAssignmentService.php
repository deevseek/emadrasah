<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\TeachingAssignment;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\{Auth, DB};
use Illuminate\Validation\ValidationException;

class TeachingAssignmentService
{
    public function __construct(private ActivityLogger $logger) {}
    public function save(array $data, ?TeachingAssignment $assignment = null): TeachingAssignment
    { return DB::transaction(function() use($data,$assignment){
        $dup=TeachingAssignment::where('academic_year_id',$data['academic_year_id'])->where('semester_id',$data['semester_id'])->where('classroom_id',$data['classroom_id'])->where('subject_id',$data['subject_id'])->where('is_active',true); if($assignment)$dup->whereKeyNot($assignment->id); if(($data['is_active']??true) && $dup->lockForUpdate()->exists()) throw ValidationException::withMessages(['subject_id'=>'Kelas, mata pelajaran, tahun ajaran, dan semester sudah memiliki penugasan aktif.']);
        $old=$assignment?->getAttributes()??[]; $assignment ? $assignment->update($data+['is_active'=>$data['is_active']??false]) : $assignment=TeachingAssignment::create($data+['is_active'=>$data['is_active']??true]);
        if($old && ($old['employee_id']??null)!=$assignment->employee_id) DB::table('teaching_assignment_histories')->insert(['teaching_assignment_id'=>$assignment->id,'old_employee_id'=>$old['employee_id']??null,'new_employee_id'=>$assignment->employee_id,'changed_by'=>Auth::id(),'notes'=>$data['notes']??null,'created_at'=>now(),'updated_at'=>now()]);
        $this->logger->log($old?'teaching-assignment.updated':'teaching-assignment.created',$assignment,$old,$assignment->getAttributes(),'Penugasan Mengajar disimpan.'); return $assignment; }); }
    public function toggle(TeachingAssignment $a, bool $active): void { DB::transaction(function() use($a,$active){ $old=$a->getAttributes(); $a->update(['is_active'=>$active]); $this->logger->log('teaching-assignment.status-changed',$a,$old,$a->getAttributes(),'Status penugasan diubah.'); }); }
}
