<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SubjectCategory;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\GradeLevel;
use App\Models\LessonSchedule;
use App\Models\Subject;
use Database\Seeders\OfficialLessonScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OfficialLessonScheduleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_uses_existing_classes_and_subjects_without_assigning_teachers(): void
    {
        $year = AcademicYear::create(['name' => '2026/2027', 'starts_on' => '2026-07-01', 'ends_on' => '2027-06-30', 'is_active' => true]);
        $level = GradeLevel::create(['name' => 'Kelas 1', 'code' => 'K1', 'level' => 1, 'is_active' => true]);

        $codes = ['PAGI', 'BTAQ', 'PP', 'QH', 'PJOK', 'TASMI', 'IST', 'LD', 'BIN', 'MTK', 'BAR', 'KNU', 'AA', 'SBDP', 'FIQ', 'BIG', 'BJW', 'NUM', 'LIT', 'LUG', 'STEAM', 'IPAS', 'SKI', 'TKA'];
        foreach ($codes as $code) {
            Subject::create(['code' => $code, 'name' => $code, 'category' => SubjectCategory::General->value, 'is_active' => true]);
        }

        foreach (['I-AS-SALAM', 'I-AR-RAHMAN', 'I-AR-RAHIM', 'II-AL-MUMIN', 'II-AL-WAHHAB', 'II-AL-LATHIF', 'III-AL-KHALIQ', 'III-AL-MAJID', 'IV-AL-BASITH', 'IV-AL-KARIM', 'V-AL-ALIM', 'VI-AL-MAJID'] as $code) {
            Classroom::create(['academic_year_id' => $year->id, 'grade_level_id' => $level->id, 'name' => $code, 'code' => $code, 'is_active' => true]);
        }

        $this->seed(OfficialLessonScheduleSeeder::class);

        $this->assertSame(12, Classroom::count());
        $this->assertSame(0, Employee::count());
        $this->assertGreaterThan(0, LessonSchedule::whereNull('employee_id')->whereNull('teaching_assignment_id')->count());
        $this->assertDatabaseHas('lesson_schedules', [
            'day_of_week' => 'senin',
            'starts_at' => '06:50',
            'ends_at' => '07:15',
            'employee_id' => null,
            'teaching_assignment_id' => null,
        ]);
    }
}
