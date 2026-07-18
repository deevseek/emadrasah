<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentScheme extends Model
{
    protected $guarded = [];
    protected $casts = ['is_active'=>'boolean','effective_start_date'=>'date','effective_end_date'=>'date'];
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function semester(): BelongsTo { return $this->belongsTo(Semester::class); }
}
