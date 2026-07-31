<?php

declare(strict_types=1);

namespace App\Services\TeachingAssignments;

use App\Models\{Subject, TeachingAssignmentSet, TeachingImportBatch, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TeachingAssignmentSetService
{
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

        return DB::transaction(fn (): TeachingAssignmentSet => $this->rebuildWithinTransaction($set, $set->teachingImportBatch, $actor));
    }

    private function rebuildWithinTransaction(TeachingAssignmentSet $set, TeachingImportBatch $batch, User $actor): TeachingAssignmentSet
    {
        (new WorkbookGradeLoadSynchronizer)->synchronize($batch, $actor);
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

        return $set->fresh()->loadCount(['assignments', 'additionalDuties']);
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
