<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const COLUMNS = [
        'foundation_employee_number',
        'nip',
        'external_employee_id',
        'rank_grade',
        'certification_subject',
        'bank_name',
        'bank_account_number',
        'phone',
        'email',
    ];

    private const PLACEHOLDERS = ['-', '–', '—', 'N/A', 'NA', 'NULL', 'NIHIL', 'TIDAK ADA'];

    public function up(): void
    {
        foreach (self::COLUMNS as $column) {
            DB::table('personnel')
                ->whereIn($column, self::PLACEHOLDERS)
                ->update([$column => null]);
        }
    }

    public function down(): void
    {
        // Placeholder yang telah dinormalisasi tidak perlu dipulihkan.
    }
};
