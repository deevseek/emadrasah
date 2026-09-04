<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, BelongsToMany, HasOne};

class Student extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['birth_date' => 'date']; }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function getGenderLabelAttribute(): string { return config("students.genders.{$this->gender}", $this->gender); }
    public function getStatusLabelAttribute(): string { return config("students.statuses.{$this->status}", $this->status); }
    public function getAgeLabelAttribute(): string { if (! $this->birth_date) return 'Umur belum diketahui'; $diff = $this->birth_date->diff(now()); return $diff->y.' tahun'.($diff->m ? ' '.$diff->m.' bulan' : ''); }
    public function getDisplayBirthInformationAttribute(): string { return collect([$this->birth_place, $this->birth_date?->translatedFormat('d F Y')])->filter()->join(', ') ?: '—'; }
    public function getParentOrGuardianNameAttribute(): string { return $this->mother_name ?: ($this->father_name ?: ($this->guardian_name ?: '—')); }
    public function getInitialsAttribute(): string { return str($this->full_name)->replaceMatches('/[^\pL\s]/u', '')->squish()->explode(' ')->take(2)->map(fn ($word) => str($word)->substr(0, 1))->join('')->upper()->toString(); }
    public function getHasSpecialConditionAttribute(): bool { return filled($this->special_needs) || filled($this->disability); }
    public function classroomMemberships(): HasMany { return $this->hasMany(ClassroomMembership::class); }
    public function activeClassroomMembership(): HasOne { return $this->hasOne(ClassroomMembership::class)->where('status', 'active')->latestOfMany(); }
    public function classrooms(): BelongsToMany { return $this->belongsToMany(Classroom::class, 'classroom_memberships')->withPivot(['status', 'joined_at', 'left_at']); }
    public function guardians(): BelongsToMany { return $this->belongsToMany(GuardianProfile::class, 'student_guardians', 'student_id', 'guardian_id')->withPivot(['relationship', 'is_primary', 'can_view_academic']); }
    public function rfidCards(): HasMany { return $this->hasMany(StudentRfidCard::class); }
    public function activeRfidCard(): HasOne { return $this->hasOne(StudentRfidCard::class)->where('is_active', true)->latestOfMany(); }
    public function getCurrentClassroomNameAttribute(): string { return $this->activeClassroomMembership?->classroom?->display_name ?: ($this->classroom_label ?: 'Belum ditempatkan'); }
}
