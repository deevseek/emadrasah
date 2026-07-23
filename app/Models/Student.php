<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AdmissionType;
use App\Enums\Gender;
use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'student_number', 'national_student_number', 'national_identity_number', 'family_card_number', 'name', 'nickname', 'gender', 'birth_place', 'birth_date', 'religion', 'citizenship', 'child_order', 'siblings_count', 'family_status', 'address', 'rt', 'rw', 'village', 'district', 'city', 'province', 'postal_code', 'phone', 'email', 'previous_school', 'previous_exam_number', 'previous_diploma_number', 'admission_date', 'admission_type', 'student_status', 'graduation_date', 'photo_path', 'notes', 'blood_type', 'weight_kg', 'height_cm', 'special_needs', 'disability', 'medical_history', 'allergies', 'bpjs_number', 'kip_pip_number', 'residence_type', 'transportation_mode', 'distance_to_school_km', 'travel_time_minutes', 'is_active'];

    protected function casts(): array
    {
        return ['gender' => Gender::class, 'birth_date' => 'date', 'admission_date' => 'date', 'admission_type' => AdmissionType::class, 'student_status' => StudentStatus::class, 'graduation_date' => 'date', 'child_order' => 'integer', 'siblings_count' => 'integer', 'weight_kg' => 'decimal:2', 'height_cm' => 'decimal:2', 'distance_to_school_km' => 'decimal:2', 'travel_time_minutes' => 'integer', 'is_active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class)
            ->withPivot(['id', 'relationship', 'is_primary', 'is_financial_responsible', 'is_emergency_contact', 'lives_with_student', 'financially_responsible', 'notes'])
            ->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function activeEnrollment(): HasOne
    {
        return $this->hasOne(StudentEnrollment::class)->where('enrollment_status', 'active')->latestOfMany();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(StudentStatusHistory::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }
}
