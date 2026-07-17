<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeroomAssignment extends Model
{
    protected $fillable = ['classroom_id', 'academic_year_id', 'employee_id', 'started_at', 'ended_at', 'is_active', 'reason', 'notes', 'assigned_by'];

    protected function casts(): array
    {
        return ['started_at' => 'date', 'ended_at' => 'date', 'is_active' => 'boolean'];
    }

    public function classroom(): BelongsTo { return $this->belongsTo(Classroom::class); }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function assignedBy(): BelongsTo { return $this->belongsTo(User::class, 'assigned_by'); }
}
