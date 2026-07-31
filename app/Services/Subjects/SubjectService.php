<?php

declare(strict_types=1);

namespace App\Services\Subjects;

use App\Enums\ClassroomProgramType;
use App\Models\{GradeLevel, Subject, SubjectGradeLoad, User};
use Illuminate\Support\Facades\DB;

class SubjectService
{
    public function create(User $actor, array $data): Subject
    {
        return DB::transaction(function () use ($actor, $data): Subject { $subject = Subject::create([...$data, 'created_by' => $actor->id, 'updated_by' => $actor->id]); activity('mata-pelajaran')->causedBy($actor)->performedOn($subject)->log("Menambahkan mata pelajaran {$subject->name}."); return $subject; });
    }
    public function update(User $actor, Subject $subject, array $data): Subject
    {
        return DB::transaction(function () use ($actor, $subject, $data): Subject { $subject->update([...$data, 'updated_by' => $actor->id]); activity('mata-pelajaran')->causedBy($actor)->performedOn($subject)->log("Memperbarui mata pelajaran {$subject->name}."); return $subject; });
    }
    public function updateLoads(User $actor, array $loads): void
    {
        DB::transaction(function () use ($actor, $loads): void {
            foreach ($this->matrixColumns() as $key => $column) foreach (Subject::all() as $subject) {
                $value = $loads[$key.'_'.$subject->id] ?? null;
                $attributes = ['subject_id' => $subject->id, 'grade_level_id' => $column['grade']->id, 'program_type' => $column['program']->value];
                blank($value) ? SubjectGradeLoad::where($attributes)->delete() : SubjectGradeLoad::updateOrCreate($attributes, ['weekly_hours' => (int) $value]);
            }
            activity('mata-pelajaran')->causedBy($actor)->withProperties(['columns' => count($this->matrixColumns())])->log('Memperbarui matriks struktur JP.');
        });
    }
    public function matrixColumns(): array
    {
        $grades = GradeLevel::orderBy('sort_order')->get()->keyBy('number'); $columns = [];
        foreach ([[1, ClassroomProgramType::FullDay, 'I Full Day'], [1, ClassroomProgramType::Regular, 'I Reguler'], [2, ClassroomProgramType::Regular, 'II'], [3, ClassroomProgramType::Regular, 'III'], [4, ClassroomProgramType::Regular, 'IV'], [5, ClassroomProgramType::Regular, 'V'], [6, ClassroomProgramType::Regular, 'VI']] as [$number, $program, $label]) if ($grades->has($number)) $columns['g'.$number.'_'.$program->value] = ['grade' => $grades[$number], 'program' => $program, 'label' => $label];
        return $columns;
    }
}
