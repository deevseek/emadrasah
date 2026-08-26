<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class AttendanceHistoryViewTest extends TestCase
{
    public function test_history_view_uses_the_shared_table_styles_and_a_mobile_layout(): void
    {
        $view = file_get_contents(resource_path('views/academic/attendance/history.blade.php'));

        $this->assertStringContainsString('class="table-wrap"', $view);
        $this->assertStringContainsString('class="data-table min-w-[800px]"', $view);
        $this->assertStringContainsString('class="card divide-y divide-slate-100 md:hidden"', $view);
        $this->assertStringContainsString('badge-success', $view);
        $this->assertStringContainsString('Belum ada data absensi sesuai filter yang dipilih.', $view);
    }

    public function test_history_filters_have_visible_labels(): void
    {
        $view = file_get_contents(resource_path('views/academic/attendance/history.blade.php'));

        $this->assertStringContainsString('<span class="label">Rombel</span>', $view);
        $this->assertStringContainsString('<span class="label">Tanggal absensi</span>', $view);
    }
}
