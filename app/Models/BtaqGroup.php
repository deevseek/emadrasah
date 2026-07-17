<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BtaqGroup extends Model
{
    public function program(): BelongsTo { return $this->belongsTo(BtaqProgram::class, 'btaq_program_id'); }
    public function teacher(): BelongsTo { return $this->belongsTo(Employee::class, 'teacher_employee_id'); }
    public function schedules(): HasMany { return $this->hasMany(BtaqSchedule::class); }

    protected $fillable = ['academic_year_id','semester_id','btaq_program_id','name','code','employee_id','teacher_employee_id','btaq_level_id','capacity','room','is_active','start_date','end_date','notes'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_required' => 'boolean',
            'needs_guidance' => 'boolean',
            'joined_at' => 'date',
            'completed_at' => 'date',
            'journal_date' => 'date',
            'assessment_date' => 'date',
            'achievement_date' => 'date',
            'report_date' => 'date',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'published_at' => 'datetime',
            'generated_at' => 'datetime',
            'approved_at' => 'datetime',
            'locked_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }

    public function students(): HasMany
    {
        return $this->hasMany(BtaqGroupStudent::class);
    }
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(BtaqLevel::class, 'btaq_level_id');
    }

}
