<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class EmployeeWorkSchedule extends Model
{
    protected $fillable = ['employee_id', 'work_schedule_id', 'effective_from', 'effective_until', 'is_active', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['effective_from' => 'date', 'effective_until' => 'date', 'is_active' => 'boolean'];
    }
}
