<?php

declare(strict_types=1);

namespace App\Services\TeachingAssignments;

use App\Models\{GradeLevel, Subject, SubjectGradeLoad, TeachingImportBatch, TeachingImportRow, User};
use Illuminate\Support\Str;

final class WorkbookGradeLoadSynchronizer
{
    /** @return array<int, Subject> subjects keyed by import-row id */
    public function synchronize(TeachingImportBatch $batch, User $actor): array
    {
        $grades = GradeLevel::whereIn('number', range(1, 6))->get()->keyBy('number');
        $subjects = [];
        $wanted = [];

        foreach ($batch->rows()->where('sheet_name', TeachingAssignmentImportService::DATABASE)->where('row_type', 'subject')->orderBy('row_number')->get() as $row) {
            $subject = $this->resolveSubject($row, $actor);
            $subjects[$row->id] = $subject;
            $loads = $row->normalized_data['loads'] ?? [];

            foreach ($this->columns() as $offset => [$gradeNumber, $program]) {
                $grade = $grades->get($gradeNumber);
                $hours = $loads[$offset] ?? null;
                if (! $grade) {
                    continue;
                }

                $key = implode(':', [$subject->id, $grade->id, $program]);
                if ($hours === null || (int) $hours <= 0) {
                    SubjectGradeLoad::where('academic_year_id', $batch->academic_year_id)
                        ->where('subject_id', $subject->id)->where('grade_level_id', $grade->id)
                        ->where('program_type', $program)->delete();
                    continue;
                }

                $wanted[] = $key;
                SubjectGradeLoad::updateOrCreate([
                    'academic_year_id' => $batch->academic_year_id,
                    'subject_id' => $subject->id,
                    'grade_level_id' => $grade->id,
                    'program_type' => $program,
                ], ['weekly_hours' => (int) $hours]);
            }
        }

        SubjectGradeLoad::where('academic_year_id', $batch->academic_year_id)->get()->each(function (SubjectGradeLoad $load) use ($wanted): void {
            if (! in_array(implode(':', [$load->subject_id, $load->grade_level_id, $load->program_type->value]), $wanted, true)) {
                $load->delete();
            }
        });

        return $subjects;
    }

    private function resolveSubject(TeachingImportRow $row, User $actor): Subject
    {
        if ($row->matched_subject_id && ($subject = Subject::find($row->matched_subject_id))) {
            return $subject;
        }

        $data = $row->normalized_data;
        $code = trim((string) ($data['source_code'] ?? ''));
        $name = trim((string) $data['source_name']);
        if ($code === '') {
            $code = Str::upper(Str::slug($name, '-'));
        }

        $subject = Subject::firstOrCreate(['name' => $name], [
            'code' => $this->availableCode($code, $name),
            'category' => $row->raw_data['category'] ?? null,
            'sort_order' => max(0, $row->row_number - 9),
            'is_active' => true,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
        $row->update(['matched_subject_id' => $subject->id, 'status' => 'matched']);

        return $subject;
    }

    private function availableCode(string $code, string $name): string
    {
        $code = mb_substr($code, 0, 30);
        if (! Subject::where('code', $code)->where('name', '!=', $name)->exists()) {
            return $code;
        }

        return mb_substr($code, 0, 23).'-'.substr(sha1($name), 0, 6);
    }

    /** @return array<int, array{int, string}> */
    private function columns(): array
    {
        return [[1, 'full_day'], [1, 'regular'], [2, 'regular'], [3, 'regular'], [4, 'regular'], [5, 'regular'], [6, 'regular']];
    }
}
