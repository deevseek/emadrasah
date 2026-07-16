<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'front_title', 'employee_number', 'nip', 'nuptk', 'national_identity_number', 'name', 'back_title', 'gender', 'birth_place', 'birth_date', 'religion', 'address', 'village', 'district', 'city', 'province', 'postal_code', 'phone', 'whatsapp', 'email', 'employment_type', 'employee_status', 'position', 'joined_at', 'left_at', 'notes', 'last_education', 'major', 'education_institution', 'graduation_year', 'photo_path', 'is_active'];

    protected function casts(): array
    {
        return ['gender' => Gender::class, 'employment_type' => EmploymentType::class, 'employee_status' => EmployeeStatus::class, 'birth_date' => 'date', 'joined_at' => 'date', 'left_at' => 'date', 'is_active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function homeroomClassrooms(): HasMany
    {
        return $this->hasMany(Classroom::class, 'homeroom_teacher_id');
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(LessonSchedule::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(EmployeeAttendance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(EmployeeLeaveRequest::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function salaryComponents(): HasMany
    {
        return $this->hasMany(\App\Models\Finance\EmployeeSalaryComponent::class);
    }

    public function fullName(): string
    {
        return trim(collect([$this->front_title, $this->name, $this->back_title])->filter()->join(' '));
    }

    public function mainNumber(): string
    {
        return $this->nip ?: ($this->nuptk ?: ($this->employee_number ?: '-'));
    }
}
