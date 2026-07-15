<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubjectCategory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\HasMany;
class Subject extends Model
{
    protected $fillable = ['code','name','category','description','minimum_passing_grade','is_active'];
    protected function casts(): array { return ['category'=>SubjectCategory::class,'minimum_passing_grade'=>'integer','is_active'=>'boolean']; }
    public function teachingAssignments(): HasMany { return $this->hasMany(TeachingAssignment::class); }
    public function schedules(): HasMany { return $this->hasMany(LessonSchedule::class); }
}
