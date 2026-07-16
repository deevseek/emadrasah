<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ChartAccount extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_cash_account' => 'boolean',
            'is_active' => 'boolean',
            'sequence' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(FinancialTransactionLine::class);
    }

    public function cashAccounts(): HasMany
    {
        return $this->hasMany(CashAccount::class);
    }

    public function feeTypes(): HasMany
    {
        return $this->hasMany(FeeType::class, 'revenue_account_id');
    }

    public function salaryExpenseComponents(): HasMany
    {
        return $this->hasMany(SalaryComponent::class, 'expense_account_id');
    }

    public function salaryPayableComponents(): HasMany
    {
        return $this->hasMany(SalaryComponent::class, 'payable_account_id');
    }
}
