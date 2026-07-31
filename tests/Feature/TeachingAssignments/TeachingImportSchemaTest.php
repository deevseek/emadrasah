<?php

declare(strict_types=1);

namespace Tests\Feature\TeachingAssignments;

use App\Models\{Personnel, Classroom};
use App\Services\TeachingAssignments\TeachingAssignmentImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class TeachingImportSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_and_legger_are_required_but_reference_sheet_is_optional(): void
    {
        $service = app(TeachingAssignmentImportService::class);
        $service->assertRequiredSheets(['DATABASE' => [], 'LEGGER' => []]);
        $this->expectException(RuntimeException::class); $service->assertRequiredSheets(['DATABASE' => []]);
    }

    public function test_preview_tables_do_not_cascade_into_personnel_or_classrooms(): void
    {
        $this->assertSame(0, Personnel::count()); $this->assertSame(0, Classroom::count());
        $this->assertDatabaseCount('teaching_import_batches', 0); $this->assertDatabaseCount('teaching_import_rows', 0);
    }
}
