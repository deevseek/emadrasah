<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SemesterType;
use App\Models\{AcademicYear, Semester, TeachingJournal, User};
use Illuminate\Database\Schema\Blueprint;
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

    public function test_migration_recovers_after_attendance_table_was_partially_created(): void
    {
        Schema::drop('teaching_journal_templates');
        Schema::drop('teaching_journal_attendances');
        Schema::create('teaching_journal_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teaching_journal_id');
            $table->foreignId('student_id');
            $table->string('status', 20);
            $table->string('notes', 1000)->nullable();
            $table->timestamps();
        });

        $migration = require database_path('migrations/2026_08_24_000000_integrate_teaching_journal_attendance_and_templates.php');
        $migration->up();

        $this->assertTrue(Schema::hasIndex('teaching_journal_attendances', 'tj_attendance_journal_student_unique'));
        $this->assertTrue(Schema::hasTable('teaching_journal_templates'));
    }

    public function test_journal_exposes_attendance_snapshots_and_report_routes(): void
    {
        $this->assertSame('teaching_journal_id', (new TeachingJournal)->attendances()->getForeignKeyName());
        $this->assertTrue(Route::has('academic.teaching-journals.template.store'));
        $this->assertTrue(Route::has('academic.teaching-journals.report'));
    }

    public function test_empty_report_redirects_to_journal_index_with_a_helpful_message(): void
    {
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'starts_at' => '2026-07-01',
            'ends_at' => '2027-06-30',
            'is_active' => true,
        ]);
        $semester = Semester::create([
            'academic_year_id' => $year->id,
            'name' => 'Semester Ganjil',
            'type' => SemesterType::Ganjil,
            'starts_at' => '2026-07-01',
            'ends_at' => '2026-12-31',
            'is_active' => true,
        ]);
        $user = User::factory()->create();
        $query = [
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'journal_date' => '2026-08-24',
        ];

        $response = $this->withoutMiddleware()->actingAs($user)->get(route(
            'academic.teaching-journals.report',
            ['format' => 'docx'] + $query,
        ));

        $response->assertRedirect(route('academic.teaching-journals.index', $query));
        $response->assertSessionHas('error', 'Laporan tidak dapat diunduh karena tidak ada jurnal pada filter yang dipilih.');
    }
}
