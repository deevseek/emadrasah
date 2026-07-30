<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AcademicYear extends Model
{
    protected $fillable = ['name', 'starts_at', 'ends_at', 'is_active', 'notes', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date', 'is_active' => 'boolean'];
    }

    public function semesters(): HasMany { return $this->hasMany(Semester::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function activeSemester(): HasOne { return $this->hasOne(Semester::class)->where('is_active', true); }
    public function getDisplayPeriodAttribute(): string { return $this->starts_at->translatedFormat('j F Y').' — '.$this->ends_at->translatedFormat('j F Y'); }
    public function getIsCurrentDateRangeAttribute(): bool { return now()->between($this->starts_at->startOfDay(), $this->ends_at->endOfDay()); }
    public function getCompletionStatusAttribute(): string { return now()->lt($this->starts_at) ? 'akan_datang' : (now()->gt($this->ends_at->endOfDay()) ? 'selesai' : 'tidak_aktif'); }
    public function getStatusLabelAttribute(): string { return $this->is_active ? 'Aktif' : match ($this->completion_status) { 'akan_datang' => 'Akan Datang', 'selesai' => 'Selesai', default => 'Tidak Aktif' }; }
}
