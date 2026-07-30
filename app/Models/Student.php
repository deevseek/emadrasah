<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
