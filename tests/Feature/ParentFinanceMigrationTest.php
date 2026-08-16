<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ParentFinanceMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_can_resume_when_its_tables_already_exist(): void
    {
        $migration = require database_path('migrations/2026_08_16_000000_create_parent_finance_schedule_and_bank_tables.php');

        $migration->up();

        $this->assertTrue(Schema::hasTable('guardian_profiles'));
        $this->assertTrue(Schema::hasTable('payroll_disbursements'));
    }
}
