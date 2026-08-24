<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class TeachingJournal extends Model
{
    protected $guarded = [];

    protected function casts(): array { return ['journal_date' => 'date']; }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function semester(): BelongsTo { return $this->belongsTo(Semester::class); }
    public function classroom(): BelongsTo { return $this->belongsTo(Classroom::class); }
    public function subject(): BelongsTo { return $this->belongsTo(AcademicSubject::class, 'academic_subject_id'); }
    public function personnel(): BelongsTo { return $this->belongsTo(Personnel::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function attendances(): HasMany { return $this->hasMany(TeachingJournalAttendance::class); }
}
