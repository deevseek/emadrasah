<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PayrollPeriod extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'year' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'payment_date' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(EmployeePayroll::class);
    }
}
