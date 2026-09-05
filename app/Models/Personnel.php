<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany,HasOne};
class Personnel extends Model
{
    protected $table='personnel'; protected $guarded=[];
    protected function casts():array{return ['birth_date'=>'date','employment_start_date'=>'date','base_salary'=>'decimal:2','weekly_teaching_hours'=>'integer','is_active'=>'boolean','payroll_enabled'=>'boolean'];}
    public function user():BelongsTo{return $this->belongsTo(User::class);}
    public function createdBy():BelongsTo{return $this->belongsTo(User::class,'created_by');}
    public function updatedBy():BelongsTo{return $this->belongsTo(User::class,'updated_by');}
    public function attendances():HasMany{return $this->hasMany(PersonnelAttendance::class);}
    public function attendanceDevices():HasMany{return $this->hasMany(PersonnelAttendanceDevice::class);}
    public function faceProfile():HasOne{return $this->hasOne(PersonnelFaceProfile::class)->where('status','active');}
    public function leaveRequests():HasMany{return $this->hasMany(PersonnelLeaveRequest::class);}
    public function payrolls():HasMany{return $this->hasMany(PersonnelPayroll::class);}
    public function cashAdvances():HasMany{return $this->hasMany(PersonnelCashAdvance::class);}
    public function getGenderLabelAttribute():string{return $this->gender==='male'?'L':'P';}
    public function getEmploymentStatusLabelAttribute():string{return config("personnel.employment_statuses.{$this->employment_status}",$this->employment_status);}
    public function getDisplayBirthInformationAttribute():string{return collect([$this->birth_place,$this->birth_date?->translatedFormat('d F Y')])->filter()->join(', ')?:'—';}
    public function getAccountStatusLabelAttribute():string
    {
        return $this->hasAccount() ? 'Terhubung' : 'Belum memiliki akun';
    }

    public function hasAccount(): bool
    {
        if (array_key_exists('has_account', $this->attributes)) {
            return (bool) $this->attributes['has_account'];
        }

        return $this->relationLoaded('user')
            ? $this->user !== null
            : $this->user()->exists();
    }
    public function getInitialsAttribute():string{return str($this->full_name)->replaceMatches('/[^\pL\s]/u','')->squish()->explode(' ')->take(2)->map(fn($w)=>str($w)->substr(0,1))->join('')->upper()->toString();}
}
