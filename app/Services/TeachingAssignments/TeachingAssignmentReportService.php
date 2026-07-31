<?php

declare(strict_types=1);
namespace App\Services\TeachingAssignments;
use App\Models\{Classroom,Personnel,TeachingAssignmentSet};
use Illuminate\Support\Collection;
class TeachingAssignmentReportService
{
 public function teachers(TeachingAssignmentSet $set,?string $search=null):Collection{return Personnel::query()->when($search,fn($q)=>$q->where('full_name','like','%'.$search.'%'))->where(fn($q)=>$q->whereHas('teachingAssignments',fn($a)=>$a->where('assignment_set_id',$set->id))->orWhereHas('additionalDuties',fn($d)=>$d->where('assignment_set_id',$set->id)))->with(['teachingAssignments'=>fn($q)=>$q->where('assignment_set_id',$set->id)->with(['classroom','subject']),'additionalDuties'=>fn($q)=>$q->where('assignment_set_id',$set->id)])->orderBy('full_name')->get()->each(function($person){$person->setAttribute('teaching_periods_total',(int)$person->teachingAssignments->sum('weekly_periods'));$person->setAttribute('additional_duty_periods_total',(int)$person->additionalDuties->sum('equivalent_periods'));$person->setAttribute('total_equivalent_periods',$person->teaching_periods_total+$person->additional_duty_periods_total);});}
 public function classrooms(TeachingAssignmentSet $set):Collection{return Classroom::with(['gradeLevel','homeroomPersonnel'])->where('academic_year_id',$set->academic_year_id)->get()->map(function($room)use($set){$target=(int)$room->gradeLevel->subjectGradeLoads()->where('program_type',$room->program_type->value)->sum('weekly_hours');$assigned=(int)$set->assignments()->where('classroom_id',$room->id)->sum('weekly_periods');$room->setAttribute('target_periods',$target);$room->setAttribute('assigned_periods',$assigned);return $room;});}
 public function personnel(TeachingAssignmentSet $set,Personnel $personnel):array{$assignments=$personnel->teachingAssignments()->where('assignment_set_id',$set->id)->with(['classroom','subject'])->get();$duties=$personnel->additionalDuties()->where('assignment_set_id',$set->id)->with('classroom')->get();$teaching=(int)$assignments->sum('weekly_periods');$additional=(int)$duties->sum('equivalent_periods');return compact('assignments','duties','teaching','additional')+['total'=>$teaching+$additional,'classroom_count'=>$assignments->pluck('classroom_id')->unique()->count(),'subject_count'=>$assignments->pluck('subject_id')->unique()->count()];}

}
