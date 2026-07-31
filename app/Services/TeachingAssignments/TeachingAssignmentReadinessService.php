<?php

declare(strict_types=1);

namespace App\Services\TeachingAssignments;

use App\Models\{Classroom, Subject, SubjectGradeLoad, TeachingAssignmentSet};
use Illuminate\Support\Collection;

final class TeachingAssignmentReadinessService
{
    public function classrooms(TeachingAssignmentSet $set): Collection
    {
        $conflicts = app(TeachingAssignmentConflictService::class)->conflicts($set);

        return Classroom::with(['gradeLevel', 'homeroomPersonnel'])
            ->where('academic_year_id', $set->academic_year_id)->where('is_active', true)->get()
            ->map(function (Classroom $room) use ($set, $conflicts): array {
                $loads = SubjectGradeLoad::with('subject')->where('academic_year_id', $set->academic_year_id)
                    ->where('grade_level_id', $room->grade_level_id)
                    ->where('program_type', $room->program_type->value)->get();
                $assignments = $set->assignments()->where('classroom_id', $room->id)->get()->groupBy('subject_id');
                $exceptions = $set->exceptions()->where('classroom_id', $room->id)->pluck('subject_id');
                $subjects = $loads->map(function (SubjectGradeLoad $load) use ($assignments, $exceptions): array {
                    $assigned = (int) ($assignments->get($load->subject_id)?->sum('weekly_periods') ?? 0);
                    $target = (int) $load->weekly_hours;
                    return ['load' => $load, 'subject' => $load->subject, 'curriculum' => $target, 'assigned' => $assigned,
                        'missing' => $exceptions->contains($load->subject_id) ? 0 : max(0, $target - $assigned),
                        'excess' => max(0, $assigned - $target)];
                });
                $missing = $subjects->where('missing', '>', 0)->pluck('load')->values();
                $target = (int) $subjects->sum('curriculum');
                $assigned = (int) $subjects->sum('assigned');
                $remaining = (int) $subjects->sum('missing');
                $excess = (int) $subjects->sum('excess');
                $conflictCount = $conflicts->filter(fn ($group) => $group->first()->classroom_id === $room->id)->count();
                $status = $conflictCount ? 'Konflik' : ($excess ? 'JP Berlebih' : ($remaining ? 'Belum Lengkap' : 'Lengkap'));
                return compact('target', 'assigned', 'remaining', 'excess') + ['classroom' => $room, 'conflicts' => $conflictCount,
                    'missing' => $missing, 'subjects' => $subjects, 'status' => $status];
            });
    }

    public function inspect(TeachingAssignmentSet $set): array
    {
        $rooms = $this->classrooms($set); $batch = $set->teachingImportBatch;
        $personnelUnmatched = $batch?->rows()->where('sheet_name', 'LEGGER')->whereNull('matched_personnel_id')->where('status', 'unmatched')->count() ?? 0;
        $classroomUnmatched = $batch?->rows()->where('sheet_name', 'LEGGER')->whereNull('matched_classroom_id')->where('status', 'unmatched')->count() ?? 0;
        $subjectUnmatched = $batch?->rows()->where('sheet_name', 'LEGGER')->whereNull('matched_subject_id')->where('status', 'unmatched')->count() ?? 0;
        $conflicts = (int) $rooms->sum('conflicts'); $excess = $rooms->where('excess', '>', 0)->count();
        $incomplete = $rooms->where('status', '!=', 'Lengkap')->count(); $issues = [];
        if ($personnelUnmatched) $issues[] = ['type' => 'personnel', 'message' => "{$personnelUnmatched} personalia belum cocok."];
        if ($classroomUnmatched) $issues[] = ['type' => 'classrooms', 'message' => "{$classroomUnmatched} rombel belum cocok."];
        if ($subjectUnmatched) $issues[] = ['type' => 'subjects', 'message' => "{$subjectUnmatched} mata pelajaran bermasalah."];
        if ($conflicts) $issues[] = ['type' => 'conflicts', 'message' => "{$conflicts} konflik pengampu belum selesai."];
        if ($excess) $issues[] = ['type' => 'excess', 'message' => "{$excess} kelas mempunyai JP berlebih."];
        if ($rooms->where('remaining', '>', 0)->isNotEmpty()) $issues[] = ['type' => 'missing', 'message' => 'Masih ada mata pelajaran wajib tanpa pengampu atau alasan pengecualian.'];
        $subjectsAvailable = Subject::where('is_active', true)->exists();
        $loadsAvailable = SubjectGradeLoad::where('academic_year_id', $set->academic_year_id)->exists();
        return compact('rooms', 'issues', 'personnelUnmatched', 'classroomUnmatched', 'subjectUnmatched', 'conflicts', 'excess', 'incomplete')
            + ['subjectsAvailable' => $subjectsAvailable, 'loadsAvailable' => $loadsAvailable,
                'ready' => $issues === [] && $subjectsAvailable && $loadsAvailable && $set->status === 'draft'];
    }
}
