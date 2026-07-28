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

        $codes = ['BTAQ', 'PKN', 'QH', 'PJOK', 'TIK', 'BINDO', 'MTK', 'BAR', 'KE-NU-AN', 'AA', 'SBDP', 'FIQ', 'BING', 'BAJA', 'NUM', 'LIT', 'LA', 'STEAM', 'IPAS', 'SKI', 'TKA', 'TAQ'];
        foreach ($codes as $code) {
            Subject::create(['code' => $code, 'name' => $code, 'category' => SubjectCategory::General->value, 'is_active' => true]);
        }

        foreach (['I-AS-SALAM', 'I-AR-RAHMAN', 'I-AR-RAHIM', 'II-AL-MUMIN', 'II-AL-WAHHAB', 'III-AL-KHALIQ', 'III-AL-LATHIF', 'IV-AL-BASITH', 'IV-AL-KARIM', 'V-AL-ALIM', 'V-AL-HAKIM', 'VI-AL-MAJID'] as $code) {
            Classroom::create(['academic_year_id' => $year->id, 'grade_level_id' => $level->id, 'name' => $code, 'code' => $code, 'is_active' => true]);
        }

        $this->seed(OfficialLessonScheduleSeeder::class);

        $this->assertSame(12, Classroom::count());
        $this->assertSame(0, Employee::count());
        $this->assertSame(0, Subject::whereIn('code', ['PAGI', 'TASMI', 'IST'])->count());
        $this->assertGreaterThan(0, LessonSchedule::whereNull('employee_id')->whereNull('teaching_assignment_id')->count());
        $this->assertDatabaseHas('lesson_schedules', [
            'day_of_week' => 'senin',
            'starts_at' => '06:50',
            'ends_at' => '07:15',
            'employee_id' => null,
            'teaching_assignment_id' => null,
            'entry_type' => 'activity',
            'activity_name' => 'Upacara + Pembiasaan Pagi + Sholat Dhuha',
            'subject_id' => null,
            'counts_as_teaching_hour' => false,
        ]);

        $this->assertScheduledSubject('I-AS-SALAM', 'senin', '12:45', 'TAQ');
        $this->assertScheduledSubject('I-AS-SALAM', 'jumat', '11:10', 'TAQ');
        $this->assertScheduledSubject('I-AS-SALAM', 'jumat', '12:25', 'TAQ');
        $this->assertScheduledSubject('I-AS-SALAM', 'sabtu', '11:45', 'TAQ');
        $this->assertScheduledSubject('I-AR-RAHIM', 'senin', '07:15', 'PKN');
        $this->assertScheduledSubject('I-AR-RAHIM', 'senin', '08:25', 'BTAQ');
        $this->assertScheduledSubject('III-AL-KHALIQ', 'selasa', '11:10', 'BAR');
        $this->assertScheduledSubject('IV-AL-BASITH', 'sabtu', '10:35', 'STEAM');
        $this->assertScheduledSubject('V-AL-HAKIM', 'senin', '09:00', 'BTAQ');
        $this->assertScheduledSubject('VI-AL-MAJID', 'sabtu', '08:25', 'TKA');
        $this->assertScheduleEntry('I-AS-SALAM', 'senin', '09:35', '10:00', 'break', 'Istirahat');
        $this->assertScheduleEntry('I-AS-SALAM', 'jumat', '11:45', '12:25', 'break', 'Ishoma/Pulang');
        $this->assertScheduleEntry('I-AS-SALAM', 'jumat', '13:00', '13:20', 'activity', 'Mission Accomplished — “Tutup Buku, Buka Cerita”');
        $this->assertScheduleEntry('I-AS-SALAM', 'senin', '15:05', '15:15', 'break', 'Istirahat/Sholat/Pulang');

        LessonSchedule::create([
            'academic_year_id' => $year->id,
            'semester_id' => $year->semesters()->firstOrFail()->id,
            'classroom_id' => Classroom::where('code', 'I-AS-SALAM')->value('id'),
            'day_of_week' => 'senin',
            'starts_at' => '16:00',
            'ends_at' => '16:30',
            'entry_type' => 'activity',
            'activity_name' => 'Kegiatan manual operator',
            'counts_as_teaching_hour' => false,
            'is_active' => true,
        ]);

        $count = LessonSchedule::count();
        $this->seed(OfficialLessonScheduleSeeder::class);
        $this->assertSame($count, LessonSchedule::count());
        $this->assertDatabaseHas('lesson_schedules', ['activity_name' => 'Kegiatan manual operator']);
    }

    private function assertScheduledSubject(string $classroomCode, string $day, string $startsAt, string $subjectCode): void
    {
        $this->assertDatabaseHas('lesson_schedules', [
            'classroom_id' => Classroom::where('code', $classroomCode)->value('id'),
            'day_of_week' => $day,
            'starts_at' => $startsAt,
            'subject_id' => Subject::where('code', $subjectCode)->value('id'),
            'entry_type' => 'lesson',
        ]);
    }

    private function assertScheduleEntry(string $classroomCode, string $day, string $startsAt, string $endsAt, string $entryType, string $activityName): void
    {
        $this->assertDatabaseHas('lesson_schedules', [
            'classroom_id' => Classroom::where('code', $classroomCode)->value('id'),
            'day_of_week' => $day,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'entry_type' => $entryType,
            'activity_name' => $activityName,
            'counts_as_teaching_hour' => false,
        ]);
    }
}
