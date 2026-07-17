<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AttendanceCorrection extends Model
{
    protected $fillable = ['employee_attendance_id', 'old_values', 'new_values', 'reason', 'corrected_by', 'corrected_at'];

    protected function casts(): array
    {
        return ['old_values' => 'array', 'new_values' => 'array', 'corrected_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class, 'corrected_by'); }
}
