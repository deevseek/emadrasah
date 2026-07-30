<?php

declare(strict_types=1);

namespace App\Services\Classrooms;

use App\Models\{Classroom, ClassroomMembership, GradeLevel, LegacyClassroomMapping, Student, User};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ClassroomLegacyAutoCreateService
{
    public function __construct(private readonly ClassroomLabelParser $parser) {}

    public function labels(int $academicYearId): Collection
    {
        return Student::query()->where('status', 'active')->whereNotNull('classroom_label')->where('classroom_label', '!=', '')
            ->whereDoesntHave('classroomMemberships', fn ($query) => $query->where('academic_year_id', $academicYearId)->where('status', 'active'))
            ->selectRaw('classroom_label, count(*) as student_count')->groupBy('classroom_label')->orderBy('classroom_label')->get()
            ->map(function (Student $row) use ($academicYearId): array {
                $parsed = $this->parser->parse((string) $row->classroom_label);
                $grade = $parsed['grade_number'] ? GradeLevel::where('number', $parsed['grade_number'])->first() : null;
                $existing = $grade && $parsed['code'] !== '' ? Classroom::where('academic_year_id', $academicYearId)->where('grade_level_id', $grade->id)->where('code', $parsed['code'])->first() : null;

                return [...$parsed, 'student_count' => (int) $row->student_count, 'grade_level_id' => $grade?->id, 'existing_classroom_id' => $existing?->id];
            });
    }

    public function process(User $actor, int $academicYearId, array $rows): array
    {
        return DB::transaction(function () use ($actor, $academicYearId, $rows): array {
            $result = ['classrooms_created' => 0, 'classrooms_reused' => 0, 'students_added' => 0, 'students_skipped' => 0, 'students_failed' => 0, 'labels_processed' => count($rows), 'failures' => []];
            $rooms = [];

            foreach ($rows as $row) {
                $key = $row['grade_level_id'].'|'.mb_strtolower(trim($row['code']));
                $alreadyResolved = isset($rooms[$key]);
                $classroom = $rooms[$key] ?? Classroom::query()->where('academic_year_id', $academicYearId)->where('grade_level_id', $row['grade_level_id'])->where('code', trim($row['code']))->lockForUpdate()->first();
                if ($classroom && ! $alreadyResolved) {
                    $result['classrooms_reused']++;
                } elseif (! $classroom) {
                    $classroom = Classroom::create(['academic_year_id' => $academicYearId, 'grade_level_id' => $row['grade_level_id'], 'code' => trim($row['code']), 'name' => filled($row['name'] ?? null) ? trim($row['name']) : null, 'capacity' => config('classrooms.default_capacity'), 'is_active' => true, 'created_by' => $actor->id, 'updated_by' => $actor->id]);
                    $result['classrooms_created']++;
                }
                $rooms[$key] = $classroom;

                $addedForLabel = 0;
                Student::query()->where('classroom_label', $row['original_label'])->lockForUpdate()->get()->each(function (Student $student) use ($actor, $academicYearId, $classroom, &$result, &$addedForLabel): void {
                    if ($student->status !== 'active' || ClassroomMembership::where('student_id', $student->id)->where('academic_year_id', $academicYearId)->where('status', 'active')->exists()) {
                        $result['students_skipped']++;
                        return;
                    }
                    ClassroomMembership::create(['student_id' => $student->id, 'classroom_id' => $classroom->id, 'academic_year_id' => $academicYearId, 'status' => 'active', 'joined_at' => today(), 'created_by' => $actor->id, 'updated_by' => $actor->id]);
                    $result['students_added']++;
                    $addedForLabel++;
                });
                LegacyClassroomMapping::updateOrCreate(['academic_year_id' => $academicYearId, 'legacy_label' => $row['original_label']], ['classroom_id' => $classroom->id, 'mapped_students_count' => $addedForLabel, 'mapped_by' => $actor->id, 'mapped_at' => now()]);
            }

            activity('kelas-rombel')->causedBy($actor)->withProperties(['academic_year_id' => $academicYearId, ...collect($result)->except(['failures'])->all()])->log("Membuat {$result['classrooms_created']} rombel dan menempatkan {$result['students_added']} siswa dari data kelas lama.");

            return $result;
        });
    }
}
