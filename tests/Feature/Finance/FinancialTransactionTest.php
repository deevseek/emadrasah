<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_module_schema_and_seed_are_available(): void
    {
        $this->seed();
        $this->assertDatabaseCount('chart_accounts', 11);
        $this->assertDatabaseHas('cash_accounts', ['name' => 'Kas Tunai Bendahara']);
        $this->assertDatabaseHas('salary_components', ['code' => 'GAJI-POKOK']);
    }
}
