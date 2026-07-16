<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Models\AcademicYear;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class BillingPeriod extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'year' => 'integer',
            'starts_on' => 'date',
            'due_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(StudentInvoice::class);
    }
}
