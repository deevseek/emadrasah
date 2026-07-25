<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Academic\Imports\{ImportMatcher, LessonScheduleImportService, SimpleXlsx};
use App\Services\Academic\{ScheduleConflictService, ScheduleService, SharedScheduleSessionValidator};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

final class AcademicImportScheduleConflictTest extends TestCase
{
    use RefreshDatabase;

    public function test_simultaneous_activities_and_breaks_without_teachers_are_accepted(): void
    {
        $activities = $this->rows(12, 'activity', 'Pembiasaan Pagi', '06:50', '07:15');
        $breaks = $this->rows(12, 'break', 'Istirahat', '09:00', '09:15', 100);

        $rows = array_merge($activities, $breaks);
        $this->classify($rows);

        $this->assertCount(24, $rows);
        $this->assertSame(['valid_new'], array_values(array_unique(array_column($rows, 'status'))));
        $this->assertNotContains('teacher_conflict', array_column($rows, 'status'));
    }

    public function test_null_employee_ids_are_not_treated_as_the_same_teacher(): void
    {
        $rows = $this->rows(2, 'activity', 'ISHOMA', '11:30', '12:30');

        $this->classify($rows);

        $this->assertSame(['valid_new', 'valid_new'], array_column($rows, 'status'));
    }

    public function test_two_lessons_with_the_same_teacher_without_shared_session_are_rejected(): void
    {
        $rows = $this->rows(2, 'lesson', null, '07:15', '07:50');
        $rows[0]['employee_id'] = $rows[1]['employee_id'] = 42;
        $rows[0]['subject_id'] = $rows[1]['subject_id'] = 7;

        $this->classify($rows);

        $this->assertSame('valid_new', $rows[0]['status']);
        $this->assertSame('teacher_conflict', $rows[1]['status']);
    }

    public function test_official_shared_session_is_accepted(): void
    {
        $rows = $this->rows(2, 'lesson', null, '07:15', '07:50');
        foreach ($rows as &$row) {
            $row['employee_id'] = 42;
            $row['subject_id'] = 7;
            $row['shared_session_code'] = 'RESMI-01';
        }

        $this->classify($rows);

        $this->assertSame(['valid_shared_session', 'valid_shared_session'], array_column($rows, 'status'));
    }

    private function classify(array &$rows): void
    {
        $service = new LessonScheduleImportService(
            new SimpleXlsx,
            new ImportMatcher,
            Mockery::mock(ScheduleService::class),
            new SharedScheduleSessionValidator,
            new ScheduleConflictService,
        );
        $method = new ReflectionMethod($service, 'classifyConflictsAndChanges');
        $method->invokeArgs($service, [&$rows]);
    }

    private function rows(int $count, string $type, ?string $activity, string $start, string $end, int $classroomOffset = 0): array
    {
        return collect(range(1, $count))->map(fn (int $classroom): array => [
            'status' => 'valid_new',
            'academic_year_id' => 1,
            'semester_id' => 1,
            'classroom_id' => $classroomOffset + $classroom,
            'assignment_id' => null,
            'employee_id' => null,
            'subject_id' => null,
            'entry_type' => $type,
            'day' => 'senin',
            'starts_at' => $start,
            'ends_at' => $end,
            'shared_session_code' => null,
            'shared_session_name' => null,
            'source' => ['nama_kegiatan' => $activity, 'ruangan' => ''],
        ])->all();
    }
}
