<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubjectCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    protected $fillable = ['code', 'name', 'short_name', 'category', 'description', 'minimum_passing_grade', 'default_weekly_hours', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['category' => SubjectCategory::class, 'minimum_passing_grade' => 'integer', 'default_weekly_hours' => 'integer', 'sort_order' => 'integer', 'is_active' => 'boolean'];
    }

    public function gradeLevels(): BelongsToMany
    {
        return $this->belongsToMany(GradeLevel::class)->withTimestamps();
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(LessonSchedule::class);
    }
}
