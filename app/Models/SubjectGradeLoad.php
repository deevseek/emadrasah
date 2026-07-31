<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClassroomProgramType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectGradeLoad extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['program_type' => ClassroomProgramType::class, 'weekly_hours' => 'integer'];
    }

    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function gradeLevel(): BelongsTo { return $this->belongsTo(GradeLevel::class); }
}
