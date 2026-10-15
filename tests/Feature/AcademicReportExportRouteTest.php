<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Academic\ReportController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AcademicReportExportRouteTest extends TestCase
{
    public function test_attendance_export_route_is_registered_with_report_permission(): void
    {
        $route = Route::getRoutes()->getByName('academic.reports.attendance.export');

        $this->assertNotNull($route);
        $this->assertSame('academic/reports/attendance/export', $route->uri());
        $this->assertContains('GET', $route->methods());
        $this->assertSame(ReportController::class.'@exportAttendance', $route->getActionName());
        $this->assertContains('permission:academic-reports.view', $route->gatherMiddleware());
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertSame(
            url('/academic/reports/attendance/export'),
            route('academic.reports.attendance.export'),
        );
    }
}
