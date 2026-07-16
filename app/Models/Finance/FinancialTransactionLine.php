<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class FinancialTransactionLine extends Model
{
    protected $guarded = [];

    protected $casts = ['debit' => 'decimal:2', 'credit' => 'decimal:2'];

    public function account() { return $this->belongsTo(ChartAccount::class, 'chart_account_id'); }
    public function cashAccount() { return $this->belongsTo(CashAccount::class); }
}
