<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Semester extends Model
{
    protected $fillable = ['academic_year_id', 'name', 'term', 'starts_on', 'ends_on', 'is_active'];
    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'is_active' => 'boolean'];
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
}
