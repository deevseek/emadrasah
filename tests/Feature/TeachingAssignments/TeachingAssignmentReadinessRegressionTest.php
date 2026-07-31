<?php

declare(strict_types=1);

namespace Tests\Feature\TeachingAssignments;

use App\Models\{AcademicYear, Classroom, GradeLevel, Subject, SubjectGradeLoad, TeachingAssignmentSet, TeachingImportBatch, TeachingImportRow, User};
use App\Services\TeachingAssignments\{TeachingAssignmentActivationService, TeachingAssignmentReadinessService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TeachingAssignmentReadinessRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_classroom_without_grade_load_is_not_complete_and_blocks_activation(): void
    {
        $data = $this->fixture();

        $inspection = app(TeachingAssignmentReadinessService::class)->inspect($data['set']);

        $this->assertSame('Struktur JP Belum Tersedia', $inspection['rooms']->first()['status']);
        $this->assertFalse($inspection['rooms']->first()['hasStructure']);
        $this->assertFalse($inspection['ready']);
        $this->assertContains('Struktur JP untuk tingkat dan program rombel ini belum tersedia.', array_column($inspection['issues'], 'message'));

        $this->expectException(ValidationException::class);
        app(TeachingAssignmentActivationService::class)->activate($data['actor'], $data['set']);
    }

    public function test_targets_for_grades_three_through_six_are_fifty_periods(): void
    {
        $data = $this->fixture(false);
        foreach (range(3, 6) as $number) {
            $grade = GradeLevel::create(['number' => $number, 'name' => "Kelas {$number}", 'roman_label' => (string) $number, 'sort_order' => $number]);
            Classroom::create(['academic_year_id' => $data['year']->id, 'grade_level_id' => $grade->id, 'program_type' => 'regular', 'code' => "{$number}-A", 'is_active' => true]);
            foreach ([20, 15, 15] as $index => $hours) {
                $subject = Subject::create(['code' => "S{$number}{$index}", 'name' => "Mapel {$number}-{$index}", 'is_active' => true]);
                SubjectGradeLoad::create(['academic_year_id' => $data['year']->id, 'subject_id' => $subject->id, 'grade_level_id' => $grade->id, 'program_type' => 'regular', 'weekly_hours' => $hours]);
            }
        }

        $targets = app(TeachingAssignmentReadinessService::class)->classrooms($data['set'])
            ->keyBy(fn (array $room) => $room['classroom']->gradeLevel->number);

        foreach (range(3, 6) as $number) $this->assertSame(50, $targets[$number]['target']);
    }

    public function test_source_entity_counts_exclude_assignment_candidates(): void
    {
        $data = $this->fixture();
        $batch = TeachingImportBatch::create(['academic_year_id' => $data['year']->id, 'user_id' => $data['actor']->id, 'original_filename' => 'uji.xlsx', 'stored_filename' => 'uji.xlsx', 'status' => 'previewed']);
        $data['set']->update(['teaching_import_batch_id' => $batch->id]);

        $this->row($batch, 10, 'classroom', 'Kelas III A', 'matched', ['matched_classroom_id' => $data['room']->id]);
        $this->row($batch, 11, 'classroom', 'Kelas III B', 'unmatched');
        $this->row($batch, 12, 'classroom', 'Kelas III B', 'unmatched'); // duplicate source entity
        $this->row($batch, 20, 'subject', 'Matematika', 'matched', ['matched_subject_id' => Subject::create(['code' => 'MTK', 'name' => 'Matematika', 'is_active' => true])->id]);
        $this->row($batch, 21, 'subject', 'Seni', 'selection');
        foreach (range(1, 5) as $sequence) {
            TeachingImportRow::create(['batch_id' => $batch->id, 'sheet_name' => 'LEGGER', 'row_number' => 30, 'source_sequence' => $sequence, 'row_type' => 'assignment_candidate', 'raw_data' => ['label' => 'calon'], 'normalized_data' => [], 'status' => 'unmatched']);
        }

        $inspection = app(TeachingAssignmentReadinessService::class)->inspect($data['set']->fresh());

        $this->assertSame(2, $inspection['classroomFound']);
        $this->assertSame(1, $inspection['classroomMatched']);
        $this->assertSame(1, $inspection['classroomUnmatched']);
        $this->assertSame(2, $inspection['subjectFound']);
        $this->assertSame(1, $inspection['subjectMatched']);
        $this->assertSame(0, $inspection['subjectUnmatched']);
        $this->assertSame(1, $inspection['subjectSelection']);
        $this->assertSame(1, $inspection['subjectProblematic']);
        $this->assertSame(5, $inspection['candidateClassroomWaiting']);
        $this->assertSame(5, $inspection['candidateSubjectWaiting']);
    }

    private function fixture(bool $withRoom = true): array
    {
        $year = AcademicYear::create(['name' => '2026/2027', 'starts_at' => '2026-07-01', 'ends_at' => '2027-06-30']);
        $actor = User::factory()->create();
        $set = TeachingAssignmentSet::create(['academic_year_id' => $year->id, 'name' => 'Draft Uji', 'status' => 'draft', 'source' => 'manual', 'created_by' => $actor->id]);
        $room = null;
        if ($withRoom) {
            $grade = GradeLevel::create(['number' => 3, 'name' => 'Kelas III', 'roman_label' => 'III', 'sort_order' => 3]);
            $room = Classroom::create(['academic_year_id' => $year->id, 'grade_level_id' => $grade->id, 'program_type' => 'regular', 'code' => 'III-A', 'is_active' => true]);
        }

        return compact('year', 'actor', 'set', 'room');
    }

    private function row(TeachingImportBatch $batch, int $number, string $type, string $name, string $status, array $matches = []): void
    {
        TeachingImportRow::create($matches + ['batch_id' => $batch->id, 'sheet_name' => 'DATABASE', 'row_number' => $number, 'source_sequence' => 0, 'row_type' => $type, 'raw_data' => ['label' => $name], 'normalized_data' => ['source_name' => $name], 'status' => $status]);
    }
}
