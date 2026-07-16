<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SalaryComponent extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:2',
            'percentage' => 'decimal:4',
            'taxable' => 'boolean',
            'is_attendance_based' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'expense_account_id');
    }

    public function payableAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'payable_account_id');
    }

    public function employeeComponents(): HasMany
    {
        return $this->hasMany(EmployeeSalaryComponent::class);
    }

    public function payrollItems(): HasMany
    {
        return $this->hasMany(EmployeePayrollItem::class);
    }
}
