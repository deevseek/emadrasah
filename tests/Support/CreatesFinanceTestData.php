<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Finance\CashAccount;
use App\Models\Finance\ChartAccount;
use App\Models\Finance\FeeType;

trait CreatesFinanceTestData
{
    protected function financeCashAccount(): CashAccount
    {
        $asset = ChartAccount::firstOrCreate(
            ['code' => 'TEST-KAS'],
            ['name' => 'Kas Test', 'account_type' => 'asset', 'normal_balance' => 'debit', 'is_cash_account' => true, 'is_active' => true]
        );

        return CashAccount::firstOrCreate(
            ['name' => 'Kas Test'],
            ['chart_account_id' => $asset->id, 'opening_balance' => 10000000, 'current_balance' => 10000000, 'is_active' => true]
        );
    }

    protected function financeRevenueFeeType(): FeeType
    {
        $revenue = ChartAccount::firstOrCreate(
            ['code' => 'TEST-PEND'],
            ['name' => 'Pendapatan Test', 'account_type' => 'revenue', 'normal_balance' => 'credit', 'is_cash_account' => false, 'is_active' => true]
        );

        return FeeType::firstOrCreate(
            ['code' => 'TEST-SPP'],
            ['name' => 'SPP Test', 'category' => 'spp', 'billing_frequency' => 'bulanan', 'default_amount' => 100000, 'is_mandatory' => true, 'is_active' => true, 'revenue_account_id' => $revenue->id]
        );
    }
}
