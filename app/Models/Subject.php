<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function teachingAssignments(): HasMany { return $this->hasMany(TeachingAssignment::class); }

    public function gradeLoads(): HasMany
    {
        return $this->hasMany(SubjectGradeLoad::class);
    }
}
