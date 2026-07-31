<?php

declare(strict_types=1);

namespace Tests\Feature\TeachingAssignments;

use App\Models\{AcademicYear, Classroom, GradeLevel, Personnel, Subject, SubjectGradeLoad, TeachingAssignment, TeachingAssignmentSet, TeachingImportBatch, TeachingImportRow, User};
use App\Services\TeachingAssignments\{TeachingAssignmentReadinessService, TeachingAssignmentSetService};
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TeachingAssignmentRebuildTest extends TestCase
{
    use RefreshDatabase;

    public function test_rebuild_button_is_only_visible_for_xlsx_draft(): void
    {
        $data = $this->fixture();
        $this->seed(AccessControlSeeder::class);
        $admin = User::where('email', 'admin@example.test')->firstOrFail();

        $this->actingAs($admin)->get(route('teaching-assignments.index', ['set' => $data['set']->id]))
            ->assertOk()->assertSee('Bangun Ulang Draft dari XLSX');

        foreach (['active', 'archived'] as $status) {
            $data['set']->update(['status' => $status]);
            $this->actingAs($admin)->get(route('teaching-assignments.index', ['set' => $data['set']->id]))
                ->assertOk()->assertDontSee('Bangun Ulang Draft dari XLSX');
        }
    }

    public function test_rebuild_synchronizes_complete_loads_and_recreates_draft_without_duplicates(): void
    {
        $data = $this->fixture();
        $this->completeWorkbookRows($data);
        $old = TeachingAssignment::create($this->assignmentPayload($data, $data['subjects'][1]));
        $this->assignmentRow($data, 1);
        $this->assignmentRow($data, 2);

        $rebuilt = app(TeachingAssignmentSetService::class)->rebuild($data['set'], $data['actor']);

        $this->assertSame(117, SubjectGradeLoad::where('academic_year_id', $data['year']->id)->count());
        $this->assertDatabaseMissing('teaching_assignments', ['id' => $old->id]);
        $this->assertSame(1, $rebuilt->assignments_count);
        $this->assertSame(1, TeachingAssignment::where('assignment_set_id', $data['set']->id)->count());
        $this->assertSame([62, 42, 42, 50, 50, 50, 50], $this->loadTotals($data));

        $rooms = app(TeachingAssignmentReadinessService::class)->classrooms($rebuilt)->keyBy(fn (array $check) => $check['classroom']->gradeLevel->number);
        foreach ([2 => 42, 3 => 50, 4 => 50, 5 => 50, 6 => 50] as $grade => $target) {
            $this->assertTrue($rooms[$grade]['hasStructure']);
            $this->assertSame($target, $rooms[$grade]['target']);
            $this->assertNotSame('Struktur JP Belum Tersedia', $rooms[$grade]['status']);
        }
    }

    public function test_failed_synchronization_rolls_back_and_active_set_is_never_changed(): void
    {
        $data = $this->fixture();
        $old = TeachingAssignment::create($this->assignmentPayload($data, $data['subjects'][1]));
        SubjectGradeLoad::create(['academic_year_id' => $data['year']->id, 'subject_id' => $data['subjects'][1]->id, 'grade_level_id' => $data['grades'][2]->id, 'program_type' => 'regular', 'weekly_hours' => 7]);

        try {
            app(TeachingAssignmentSetService::class)->rebuild($data['set'], $data['actor']);
            $this->fail('Rebuild dengan Struktur JP tidak lengkap seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertSame('Struktur JP dari workbook belum berhasil disinkronkan. Draft tidak diubah.', $exception->errors()['structure'][0]);
        }

        $this->assertDatabaseHas('teaching_assignments', ['id' => $old->id]);
        $this->assertDatabaseHas('subject_grade_loads', ['weekly_hours' => 7]);

        $data['set']->update(['status' => 'active']);
        $this->expectException(ValidationException::class);
        app(TeachingAssignmentSetService::class)->rebuild($data['set']->fresh(), $data['actor']);
    }

    public function test_success_message_is_only_returned_after_complete_loads_are_stored(): void
    {
        $data = $this->fixture();
        $this->completeWorkbookRows($data);
        $this->seed(AccessControlSeeder::class);
        $admin = User::where('email', 'admin@example.test')->firstOrFail();

        $this->actingAs($admin)->post(route('teaching-assignments.sets.rebuild', $data['set']))
            ->assertRedirect(route('teaching-assignments.index', ['set' => $data['set']->id, 'tab' => 'inspection']))
            ->assertSessionHas('success', 'Draft berhasil dibangun ulang. 117 Struktur JP dan penugasan dari XLSX telah disinkronkan.');
        $this->assertSame(117, SubjectGradeLoad::where('academic_year_id', $data['year']->id)->count());
    }

    private function fixture(): array
    {
        $year = AcademicYear::create(['name' => '2026/2027', 'starts_at' => '2026-07-01', 'ends_at' => '2027-06-30']);
        $actor = User::factory()->create();
        $batch = TeachingImportBatch::create(['academic_year_id' => $year->id, 'user_id' => $actor->id, 'original_filename' => 'pembagian.xlsx', 'stored_filename' => 'pembagian.xlsx', 'status' => 'previewed']);
        $set = TeachingAssignmentSet::create(['academic_year_id' => $year->id, 'teaching_import_batch_id' => $batch->id, 'name' => 'Draft XLSX', 'status' => 'draft', 'source' => 'xlsx_import', 'created_by' => $actor->id]);
        $grades = [];
        $rooms = [];
        foreach (range(1, 6) as $number) {
            $grades[$number] = GradeLevel::create(['number' => $number, 'name' => "Kelas {$number}", 'roman_label' => (string) $number, 'sort_order' => $number]);
            if ($number >= 2) $rooms[$number] = Classroom::create(['academic_year_id' => $year->id, 'grade_level_id' => $grades[$number]->id, 'program_type' => 'regular', 'code' => "{$number}-A", 'is_active' => true]);
        }
        $subjects = [];
        foreach (range(1, 22) as $number) $subjects[$number] = Subject::create(['code' => "M{$number}", 'name' => "Mapel {$number}", 'is_active' => true]);
        $personnel = Personnel::create(['full_name' => 'Guru Rebuild', 'gender' => 'male', 'employment_status' => 'GTY', 'position' => 'Guru', 'is_active' => true]);

        return compact('year', 'actor', 'batch', 'set', 'grades', 'rooms', 'subjects', 'personnel');
    }

    private function completeWorkbookRows(array $data): void
    {
        $counts = [17, 14, 14, 18, 18, 18, 18];
        $totals = [62, 42, 42, 50, 50, 50, 50];
        foreach ($data['subjects'] as $number => $subject) {
            $loads = [];
            foreach ($counts as $offset => $count) $loads[] = $number <= $count ? ($number === 1 ? 1 + $totals[$offset] - $count : 1) : null;
            TeachingImportRow::create(['batch_id' => $data['batch']->id, 'sheet_name' => 'DATABASE', 'row_number' => 9 + $number, 'source_sequence' => 0, 'row_type' => 'subject', 'raw_data' => [], 'normalized_data' => ['source_name' => $subject->name, 'loads' => $loads], 'matched_subject_id' => $subject->id, 'status' => 'matched']);
        }
    }

    private function assignmentRow(array $data, int $sequence): void
    {
        TeachingImportRow::create(['batch_id' => $data['batch']->id, 'sheet_name' => 'LEGGER', 'row_number' => 5, 'source_sequence' => $sequence, 'row_type' => 'assignment_candidate', 'raw_data' => [], 'normalized_data' => ['personnel_ids' => [$data['personnel']->id], 'subject_ids' => [$data['subjects'][1]->id], 'classroom_ids' => [$data['rooms'][2]->id]], 'matched_personnel_id' => $data['personnel']->id, 'matched_subject_id' => $data['subjects'][1]->id, 'matched_classroom_id' => $data['rooms'][2]->id, 'status' => 'matched']);
    }

    private function assignmentPayload(array $data, Subject $subject): array
    {
        return ['assignment_set_id' => $data['set']->id, 'academic_year_id' => $data['year']->id, 'classroom_id' => $data['rooms'][2]->id, 'subject_id' => $subject->id, 'personnel_id' => $data['personnel']->id, 'weekly_periods' => 1, 'teacher_role' => 'primary', 'is_primary' => true];
    }

    private function loadTotals(array $data): array
    {
        return [
            SubjectGradeLoad::where('grade_level_id', $data['grades'][1]->id)->where('program_type', 'full_day')->sum('weekly_hours'),
            ...collect(range(1, 6))->map(fn (int $grade) => SubjectGradeLoad::where('grade_level_id', $data['grades'][$grade]->id)->where('program_type', 'regular')->sum('weekly_hours'))->all(),
        ];
    }
}
