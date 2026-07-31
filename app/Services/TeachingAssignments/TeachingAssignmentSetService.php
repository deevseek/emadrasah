<?php

declare(strict_types=1);

namespace App\Services\TeachingAssignments;

use App\Models\{Subject, SubjectGradeLoad, TeachingAssignmentSet, TeachingImportBatch, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TeachingAssignmentSetService
{
    private const EXPECTED_LOAD_COUNT = 117;

    private const EXPECTED_TOTALS = [
        '1:full_day' => 62,
        '1:regular' => 42,
        '2:regular' => 42,
        '3:regular' => 50,
        '4:regular' => 50,
        '5:regular' => 50,
        '6:regular' => 50,
    ];

    public function fromImport(TeachingImportBatch $batch, User $actor): TeachingAssignmentSet
    {
        return DB::transaction(function () use ($batch, $actor): TeachingAssignmentSet {
            if (! in_array($batch->status, ['previewed', 'saved_as_draft'], true)) {
                throw ValidationException::withMessages(['batch' => 'Batch import belum siap disimpan sebagai draft.']);
            }

            $set = TeachingAssignmentSet::firstOrCreate([
                'teaching_import_batch_id' => $batch->id,
                'status' => 'draft',
            ], [
                'academic_year_id' => $batch->academic_year_id,
                'name' => 'Draft '.$batch->original_filename,
                'source' => 'xlsx_import',
                'source_filename' => $batch->original_filename,
                'created_by' => $actor->id,
            ]);

            return $this->rebuildWithinTransaction($set, $batch, $actor);
        });
    }

    public function rebuild(TeachingAssignmentSet $set, User $actor): TeachingAssignmentSet
    {
        if (! $set->isEditable()) {
            throw ValidationException::withMessages(['set' => 'Hanya set berstatus draft yang dapat dibangun ulang.']);
        }
        if (! $set->teachingImportBatch) {
            throw ValidationException::withMessages(['set' => 'Draft tidak mempunyai batch import sumber.']);
        }

        return DB::transaction(function () use ($set, $actor): TeachingAssignmentSet {
            $lockedSet = TeachingAssignmentSet::query()->lockForUpdate()->findOrFail($set->id);
            if (! $lockedSet->isEditable()) {
                throw ValidationException::withMessages(['set' => 'Hanya set berstatus draft yang dapat dibangun ulang.']);
            }

            $batch = $lockedSet->teachingImportBatch;
            if (! $batch) {
                throw ValidationException::withMessages(['set' => 'Draft tidak mempunyai batch import sumber.']);
            }

            return $this->rebuildWithinTransaction($lockedSet, $batch, $actor);
        });
    }

    private function rebuildWithinTransaction(TeachingAssignmentSet $set, TeachingImportBatch $batch, User $actor): TeachingAssignmentSet
    {
        (new WorkbookGradeLoadSynchronizer)->synchronize($batch, $actor);
        $this->ensureWorkbookLoadsAreComplete($set);
        $this->refreshCandidateSubjects($batch);

        $set->assignments()->delete();
        $set->additionalDuties()->delete();
        $set->exceptions()->delete();

        $assignments = app(TeachingAssignmentService::class);
        $duties = app(TeachingAssignmentDutyService::class);
        foreach ($batch->rows()->where('sheet_name', TeachingAssignmentImportService::LEGGER)->get() as $row) {
            if ($row->row_type === 'assignment_candidate') {
                foreach ($row->normalized_data['personnel_ids'] ?? [] as $personnelId) {
                    foreach ($row->normalized_data['subject_ids'] ?? [] as $subjectId) {
                        foreach ($row->normalized_data['classroom_ids'] ?? [] as $classroomId) {
                            $assignments->save($actor, $set, [
                                'personnel_id' => $personnelId,
                                'subject_id' => $subjectId,
                                'classroom_id' => $classroomId,
                                'teacher_role' => 'primary',
                                'notes' => 'Hasil import '.$batch->original_filename,
                            ]);
                        }
                    }
                }
            }

            if ($row->row_type === 'additional_duty' && $row->matched_personnel_id) {
                $text = mb_strtolower((string) ($row->normalized_data['duty_name'] ?? ''));
                $type = match (true) {
                    str_contains($text, 'kepala madrasah') => 'headmaster',
                    str_contains($text, 'wali kelas') => 'homeroom_teacher',
                    str_contains($text, 'operator') => 'operator',
                    str_contains($text, 'staff tu'), str_contains($text, 'staf tu') => 'administration_staff',
                    default => null,
                };
                if ($type) {
                    $duties->save($actor, $set, [
                        'personnel_id' => $row->matched_personnel_id,
                        'classroom_id' => $type === 'homeroom_teacher' ? $row->matched_classroom_id : null,
                        'duty_type' => $type,
                        'duty_name' => ucwords(str_replace('_', ' ', $type)),
                        'notes' => 'Hasil import LEGGER',
                    ]);
                }
            }
        }

        $batch->update(['status' => 'saved_as_draft']);
        activity('teaching-assignments')->causedBy($actor)->performedOn($set)
            ->log('Membangun draft dari Struktur JP dan LEGGER batch import.');

        app(TeachingAssignmentReadinessService::class)->inspect($set);

        return $set->fresh()->loadCount(['assignments', 'additionalDuties']);
    }

    private function ensureWorkbookLoadsAreComplete(TeachingAssignmentSet $set): void
    {
        $totals = SubjectGradeLoad::query()
            ->join('grade_levels', 'grade_levels.id', '=', 'subject_grade_loads.grade_level_id')
            ->where('subject_grade_loads.academic_year_id', $set->academic_year_id)
            ->selectRaw('grade_levels.number as grade_number, subject_grade_loads.program_type, SUM(subject_grade_loads.weekly_hours) as total')
            ->groupBy('grade_levels.number', 'subject_grade_loads.program_type')
            ->get()
            ->mapWithKeys(fn ($load): array => [
                $load->grade_number.':'.$load->getRawOriginal('program_type') => (int) $load->total,
            ]);

        $count = SubjectGradeLoad::where('academic_year_id', $set->academic_year_id)->count();
        $actualTotals = $totals->all();
        ksort($actualTotals);
        $expectedTotals = self::EXPECTED_TOTALS;
        ksort($expectedTotals);
        if ($count !== self::EXPECTED_LOAD_COUNT || $actualTotals !== $expectedTotals) {
            throw ValidationException::withMessages([
                'structure' => 'Struktur JP dari workbook belum berhasil disinkronkan. Draft tidak diubah.',
            ]);
        }
    }

    private function refreshCandidateSubjects(TeachingImportBatch $batch): void
    {
        $subjects = Subject::where('is_active', true)->get();
        $matcher = new WorkbookNameMatcher;
        foreach ($batch->rows()->where('sheet_name', TeachingAssignmentImportService::LEGGER)->where('row_type', 'assignment_candidate')->get() as $row) {
            $match = $matcher->match((string) ($row->normalized_data['subject_source'] ?? ''), $subjects, 'name');
            if ($match['status'] !== 'matched') {
                continue;
            }
            $normalized = $row->normalized_data;
            $normalized['subject_ids'] = [$match['match']->id];
            $normalized['component_statuses']['subject'] = 'matched';
            $row->update(['matched_subject_id' => $match['match']->id, 'normalized_data' => $normalized]);
        }
    }
}
