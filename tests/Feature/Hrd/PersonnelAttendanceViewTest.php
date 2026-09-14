<?php

declare(strict_types=1);

namespace Tests\Feature\Hrd;

use Tests\TestCase;

class PersonnelAttendanceViewTest extends TestCase
{
    public function test_index_has_distinct_desktop_table_and_mobile_cards(): void
    {
        $view = file_get_contents(resource_path('views/hrd/attendance/index.blade.php'));

        $this->assertStringContainsString('class="hidden md:block"', $view);
        $this->assertStringContainsString('class="data-table min-w-[960px]"', $view);
        $this->assertStringContainsString('class="card divide-y divide-slate-100 md:hidden"', $view);
        $this->assertStringContainsString('grid grid-cols-3', $view);
    }

    public function test_filters_remain_selected_and_have_visible_labels(): void
    {
        $view = file_get_contents(resource_path('views/hrd/attendance/index.blade.php'));

        $this->assertStringContainsString('<span>Status kehadiran</span>', $view);
        $this->assertStringContainsString('<span>Shift kerja</span>', $view);
        $this->assertStringContainsString('<span>Metode absensi</span>', $view);
        $this->assertStringContainsString("@selected(request('method') === $value)", $view);
        $this->assertStringContainsString("@selected((string) request('shift_number') === (string) $shift)", $view);
    }
}
