<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\TeachingJournal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Route, Schema};
use Tests\TestCase;

class TeachingJournalIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_journal_attendance_and_template_tables_are_available(): void
    {
        $this->assertTrue(Schema::hasColumns('teaching_journal_attendances', ['teaching_journal_id', 'student_id', 'status', 'notes']));
        $this->assertTrue(Schema::hasColumns('teaching_journal_templates', ['name', 'original_name', 'path', 'is_active', 'uploaded_by']));
    }

    public function test_journal_exposes_attendance_snapshots_and_report_routes(): void
    {
        $this->assertSame('teaching_journal_id', (new TeachingJournal)->attendances()->getForeignKeyName());
        $this->assertTrue(Route::has('academic.teaching-journals.template.store'));
        $this->assertTrue(Route::has('academic.teaching-journals.report'));
    }
}
