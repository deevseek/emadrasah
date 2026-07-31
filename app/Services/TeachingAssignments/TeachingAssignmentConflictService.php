<?php

declare(strict_types=1);

namespace App\Services\TeachingAssignments;

use App\Models\{TeachingAssignment, TeachingAssignmentSet, User};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeachingAssignmentConflictService
{
    public function conflicts(TeachingAssignmentSet $set): Collection
    {
        return TeachingAssignment::with(['classroom', 'subject', 'personnel'])->where('assignment_set_id', $set->id)->where('is_primary', true)->get()
            ->groupBy(fn ($assignment) => $assignment->classroom_id.'_'.$assignment->subject_id)
            ->filter(fn (Collection $assignments) => $assignments->count() > 1)->values();
    }

    public function resolve(User $actor, TeachingAssignmentSet $set, int $classroomId, int $subjectId, string $strategy, array $periods = [], ?int $primaryId = null): void
    {
        if (! $set->isEditable()) throw ValidationException::withMessages(['set' => 'Hanya draft yang dapat diperbaiki.']);
        DB::transaction(function () use ($actor, $set, $classroomId, $subjectId, $strategy, $periods, $primaryId): void {
            $assignments = $set->assignments()->with(['classroom', 'subject'])->where('classroom_id', $classroomId)->where('subject_id', $subjectId)->get();
            if ($assignments->count() < 2) throw ValidationException::withMessages(['conflict' => 'Konflik pengampu tidak ditemukan.']);
            $target = app(TeachingAssignmentService::class)->officialLoad($assignments->first()->classroom, $assignments->first()->subject);
            if ($strategy === 'single') {
                if (! $assignments->contains('personnel_id', $primaryId)) throw ValidationException::withMessages(['primary_personnel_id' => 'Guru utama tidak termasuk dalam konflik.']);
                $assignments->where('personnel_id', '!=', $primaryId)->each->delete();
                $assignments->firstWhere('personnel_id', $primaryId)->update(['is_primary' => true, 'teacher_role' => 'primary', 'weekly_periods' => $target, 'updated_by' => $actor->id]);
            } else {
                if (array_sum(array_map('intval', $periods)) !== $target) throw ValidationException::withMessages(['periods' => "Total JP pengampu bersama harus tepat {$target} JP."]);
                if (! $primaryId || ! array_key_exists((string) $primaryId, $periods)) throw ValidationException::withMessages(['primary_personnel_id' => 'Salah satu guru wajib ditandai sebagai guru utama.']);
                foreach ($assignments as $assignment) {
                    $value = (int) ($periods[$assignment->personnel_id] ?? 0);
                    if ($value < 1) throw ValidationException::withMessages(['periods' => 'JP setiap pengampu bersama minimal 1 JP.']);
                    $assignment->update(['weekly_periods' => $value, 'is_primary' => $assignment->personnel_id === $primaryId, 'teacher_role' => $assignment->personnel_id === $primaryId ? 'primary' : 'co_teacher', 'updated_by' => $actor->id]);
                }
            }
            $first = $assignments->first();
            activity('teaching-assignments')->causedBy($actor)->performedOn($set)->log("Menyelesaikan konflik pengampu {$first->subject->name} {$first->classroom->display_name}.");
        });
    }
}
