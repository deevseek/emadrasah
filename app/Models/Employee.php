<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmployeeStatus; use App\Enums\EmploymentType; use App\Enums\Gender;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasMany; use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;
    protected $fillable = ['user_id','employee_number','national_identity_number','name','gender','birth_place','birth_date','address','phone','email','employment_type','employee_status','joined_at','left_at','photo_path','is_active'];
    protected function casts(): array { return ['gender'=>Gender::class,'employment_type'=>EmploymentType::class,'employee_status'=>EmployeeStatus::class,'birth_date'=>'date','joined_at'=>'date','left_at'=>'date','is_active'=>'boolean']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function homeroomClassrooms(): HasMany { return $this->hasMany(Classroom::class, 'homeroom_teacher_id'); }
    public function teachingAssignments(): HasMany { return $this->hasMany(TeachingAssignment::class); }
    public function schedules(): HasMany { return $this->hasMany(LessonSchedule::class); }
}
