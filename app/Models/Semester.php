<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SemesterType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Semester extends Model
{
    protected $fillable = ['academic_year_id', 'name', 'type', 'starts_at', 'ends_at', 'is_active', 'notes', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['type' => SemesterType::class, 'starts_at' => 'date', 'ends_at' => 'date', 'is_active' => 'boolean'];
    }

    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function getDisplayNameAttribute(): string { return $this->type->label(); }
    public function getDisplayPeriodAttribute(): string { return $this->starts_at->translatedFormat('j F Y').' — '.$this->ends_at->translatedFormat('j F Y'); }
    public function getStatusLabelAttribute(): string { if ($this->is_active) return 'Aktif'; if (now()->lt($this->starts_at)) return 'Akan Datang'; if (now()->gt($this->ends_at->endOfDay())) return 'Selesai'; return 'Tidak Aktif'; }
}
