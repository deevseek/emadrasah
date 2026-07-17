<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class WorkSchedule extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'employee_type', 'working_days', 'check_in_time', 'late_tolerance_minutes', 'check_out_time', 'earliest_check_in_time', 'earliest_check_out_time', 'is_active', 'description', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['working_days' => 'array', 'is_active' => 'boolean'];
    }
}
