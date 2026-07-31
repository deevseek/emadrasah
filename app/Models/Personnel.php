<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};
class Personnel extends Model
{
    protected $table='personnel'; protected $guarded=[];
    protected function casts():array{return ['birth_date'=>'date','weekly_teaching_hours'=>'integer','is_active'=>'boolean'];}
    public function teachingAssignments():HasMany{return $this->hasMany(TeachingAssignment::class);}
    public function additionalDuties():HasMany{return $this->hasMany(AdditionalDuty::class);}
    public function teachingPeriodsTotal(TeachingAssignmentSet $set):int{return (int)$this->teachingAssignments()->where('assignment_set_id',$set->id)->sum('weekly_periods');}
    public function additionalDutyPeriodsTotal(TeachingAssignmentSet $set):int{return (int)$this->additionalDuties()->where('assignment_set_id',$set->id)->sum('equivalent_periods');}
    public function totalEquivalentPeriods(TeachingAssignmentSet $set):int{return $this->teachingPeriodsTotal($set)+$this->additionalDutyPeriodsTotal($set);}
    public function user():BelongsTo{return $this->belongsTo(User::class);}
    public function createdBy():BelongsTo{return $this->belongsTo(User::class,'created_by');}
    public function updatedBy():BelongsTo{return $this->belongsTo(User::class,'updated_by');}
    public function getGenderLabelAttribute():string{return $this->gender==='male'?'L':'P';}
    public function getEmploymentStatusLabelAttribute():string{return config("personnel.employment_statuses.{$this->employment_status}",$this->employment_status);}
    public function getDisplayBirthInformationAttribute():string{return collect([$this->birth_place,$this->birth_date?->translatedFormat('d F Y')])->filter()->join(', ')?:'—';}
    public function getAccountStatusLabelAttribute():string{return $this->user_id?'Terhubung':'Belum memiliki akun';}
    public function getInitialsAttribute():string{return str($this->full_name)->replaceMatches('/[^\pL\s]/u','')->squish()->explode(' ')->take(2)->map(fn($w)=>str($w)->substr(0,1))->join('')->upper()->toString();}
}
