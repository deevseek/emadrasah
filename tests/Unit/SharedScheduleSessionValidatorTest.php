<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Academic\SharedScheduleSessionValidator;
use PHPUnit\Framework\TestCase;

final class SharedScheduleSessionValidatorTest extends TestCase
{
    public function test_same_teacher_subject_and_time_in_different_classes_is_valid(): void
    {
        $rows = [$this->row(1), $this->row(2)];

        $this->assertSame([], (new SharedScheduleSessionValidator)->validate($rows));
    }

    public function test_different_teacher_gets_specific_status_instead_of_teacher_conflict(): void
    {
        $rows = [$this->row(1), $this->row(2, ['employee_id' => 99])];

        $this->assertSame('shared_session_teacher_mismatch', (new SharedScheduleSessionValidator)->validate($rows)[1]);
    }

    public function test_different_subject_gets_specific_status(): void
    {
        $rows = [$this->row(1), $this->row(2, ['subject_id' => 99])];

        $this->assertSame('shared_session_subject_mismatch', (new SharedScheduleSessionValidator)->validate($rows)[1]);
    }

    public function test_same_class_in_same_slot_is_rejected(): void
    {
        $rows = [$this->row(1), $this->row(1)];
        $errors = (new SharedScheduleSessionValidator)->validate($rows);

        $this->assertSame('shared_session_duplicate_class', $errors[0]);
        $this->assertSame('shared_session_duplicate_class', $errors[1]);
    }

    public function test_different_time_is_a_separate_shared_session_slot(): void
    {
        $rows = [$this->row(1), $this->row(1, ['starts_at' => '10:35', 'ends_at' => '11:10'])];

        $this->assertSame([], (new SharedScheduleSessionValidator)->validate($rows));
    }

    private function row(int $classroom, array $changes = []): array
    {
        return $changes + ['entry_type' => 'lesson', 'assignment_id' => $classroom, 'employee_id' => 10, 'subject_id' => 20,
            'shared_session_code' => 'BAR-GABUNG', 'shared_session_name' => 'Bahasa Arab gabungan kelas IV dan V',
            'academic_year_id' => 1, 'semester_id' => 1, 'day' => 'jumat', 'starts_at' => '10:00', 'ends_at' => '10:35', 'classroom_id' => $classroom];
    }
}
