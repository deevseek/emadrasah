<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class RfidAttendanceLiveTest extends TestCase
{
    public function test_live_endpoint_requires_authentication(): void
    {
        $this->getJson(route('academic.attendance.live', ['classroom_id'=>1,'attendance_date'=>today()->toDateString(),'cursor'=>0]))->assertUnauthorized();
    }

    public function test_attendance_view_contains_live_polling_contract_and_dirty_protection(): void
    {
        $view = file_get_contents(resource_path('views/academic/attendance/index.blade.php'));
        $this->assertStringContainsString('data-attendance-student', $view);
        $this->assertStringContainsString('document.visibilityState', $view);
        $this->assertStringContainsString('dirty.has(id)', $view);
        $this->assertStringContainsString('2500', $view);
        $this->assertStringContainsString('$date === today()->toDateString()', $view);
    }
}
