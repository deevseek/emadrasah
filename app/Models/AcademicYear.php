<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    protected $fillable = ['name', 'starts_on', 'ends_on', 'is_active'];
    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'is_active' => 'boolean'];
    public function semesters(): HasMany { return $this->hasMany(Semester::class); }
}
