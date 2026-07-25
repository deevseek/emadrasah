<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    protected $fillable = ['type','original_filename','academic_year_id','semester_id','status','total_rows','valid_rows','imported_rows','skipped_rows','error_rows','imported_by','started_at','finished_at','metadata'];
    protected function casts(): array { return ['metadata'=>'array','started_at'=>'datetime','finished_at'=>'datetime']; }
    public function importer(): BelongsTo { return $this->belongsTo(User::class, 'imported_by'); }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function semester(): BelongsTo { return $this->belongsTo(Semester::class); }
    public function teachingAssignments(): HasMany { return $this->hasMany(TeachingAssignment::class); }
    public function schedules(): HasMany { return $this->hasMany(LessonSchedule::class); }
}
