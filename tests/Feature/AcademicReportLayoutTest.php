<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class AcademicReportLayoutTest extends TestCase
{
    public function test_academic_report_uses_professional_responsive_table_components(): void
    {
        $view = file_get_contents(resource_path('views/academic/reports/index.blade.php'));

        $this->assertStringContainsString('Filter Laporan', $view);
        $this->assertStringContainsString('Ringkasan jurnal mengajar', $view);
        $this->assertStringContainsString('table-wrap', $view);
        $this->assertStringContainsString('data-table', $view);
        $this->assertStringContainsString('Rekap per Guru', $view);
        $this->assertStringContainsString('Rekap per Rombel', $view);
        $this->assertStringNotContainsString('class="table"', $view);
    }

    public function test_academic_report_query_respects_semester_and_optional_classroom(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Academic/ReportController.php'));

        $this->assertStringContainsString("->where('semester_id', \$semester)", $controller);
        $this->assertStringContainsString("\$teachingQuery->where('classroom_id', \$roomId)", $controller);
    }
}
