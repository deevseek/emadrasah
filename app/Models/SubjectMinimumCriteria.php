<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectMinimumCriteria extends Model
{
    protected $table = 'subject_minimum_criteria'; protected $guarded = [];
    protected $casts = ['is_active'=>'boolean','minimum_score'=>'decimal:2','effective_start_date'=>'date','effective_end_date'=>'date'];
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function classroom(): BelongsTo { return $this->belongsTo(Classroom::class); }
}
