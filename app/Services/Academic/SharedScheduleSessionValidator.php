<?php

declare(strict_types=1);

namespace App\Services\Academic;

final class SharedScheduleSessionValidator
{
    /** @return array<int, string> keyed by preview row index */
    public function validate(array $rows): array
    {
        $errors = [];
        $groups = collect($rows)->filter(fn (array $row) => filled($row['shared_session_code'] ?? null))->groupBy('shared_session_code', true);

        foreach ($groups as $group) {
            $first = $group->first();
            foreach ($group as $index => $row) {
                if (($row['entry_type'] ?? null) !== 'lesson' || empty($row['assignment_id']) || empty($row['employee_id']) || empty($row['subject_id']) || blank($row['shared_session_name'] ?? null)) {
                    $errors[$index] = 'shared_session_invalid';
                } elseif ($row['employee_id'] !== $first['employee_id']) {
                    $errors[$index] = 'shared_session_teacher_mismatch';
                } elseif ($row['subject_id'] !== $first['subject_id']) {
                    $errors[$index] = 'shared_session_subject_mismatch';
                } elseif (collect(['academic_year_id', 'semester_id', 'day'])->contains(fn ($key) => $row[$key] !== $first[$key])) {
                    $errors[$index] = 'shared_session_invalid';
                }
            }

            foreach ($group->groupBy(fn ($row) => $row['day'].':'.$row['starts_at'].':'.$row['ends_at'], true) as $slot) {
                foreach ($slot->groupBy('classroom_id', true)->filter(fn ($sameClass) => $sameClass->count() > 1) as $duplicates) {
                    foreach ($duplicates as $index => $_) {
                        $errors[$index] = 'shared_session_duplicate_class';
                    }
                }
            }
        }

        return $errors;
    }
}
