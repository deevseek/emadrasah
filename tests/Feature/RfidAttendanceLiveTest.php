<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{AcademicYear, Classroom, GradeLevel, RfidAttendanceEvent, Student, User};
use App\Services\Academic\LiveAttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfidAttendanceLiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_endpoint_requires_authentication(): void
    {
        $this->getJson(route('academic.attendance.live', ['classroom_id'=>1,'attendance_date'=>today()->toDateString(),'cursor'=>0]))->assertUnauthorized();
    }

    public function test_attendance_view_contains_live_polling_contract_and_dirty_protection(): void
    {
        $view = file_get_contents(resource_path('views/academic/attendance/index.blade.php'));
        $this->assertStringContainsString('data-attendance-student', $view);
        $this->assertStringContainsString('document.visibilityState', $view);
        $this->assertStringContainsString('dirty.has(id)', $view);
        $this->assertStringContainsString('handledEventIds.has(eventId)', $view);
        $this->assertStringContainsString('toastQueue.shift()', $view);
        $this->assertStringContainsString('let cursor=@json((int)$liveCursor)', $view);
        $this->assertStringContainsString('data.events.forEach(apply)', $view);
        $this->assertStringNotContainsString('card_token', $view);
        $this->assertStringNotContainsString('X-Device-Token', $view);
        $this->assertStringContainsString('2500', $view);
        $this->assertStringContainsString('$date === today()->toDateString()', $view);
        $this->assertStringContainsString("event.code==='ALREADY_ATTENDED'", $view);
        $this->assertStringContainsString("'⚠ SUDAH ABSEN'", $view);
        $this->assertStringContainsString("'✕ ABSENSI GAGAL'", $view);
        $this->assertStringContainsString('consecutiveFailures<3', $view);
        $this->assertStringContainsString('Live RFID menyambungkan ulang...', $view);
        $this->assertStringContainsString("status.textContent='● Live tersambung'", $view);
        $this->assertStringContainsString("data.reader.online?'● Reader Online':'● Reader Offline'", $view);
        $this->assertStringNotContainsString('Live RFID terputus', $view);
    }

    public function test_live_feed_uses_the_available_nisn_student_column(): void
    {
        $user = User::factory()->create();
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'starts_at' => '2026-07-01',
            'ends_at' => '2027-06-30',
            'is_active' => true,
        ]);
        $grade = GradeLevel::create([
            'number' => 1,
            'name' => 'Kelas I',
            'roman_label' => 'I',
            'sort_order' => 1,
        ]);
        $classroom = Classroom::create([
            'academic_year_id' => $year->id,
            'grade_level_id' => $grade->id,
            'code' => 'A',
        ]);
        $student = Student::create([
            'full_name' => 'Ahmad Fulan',
            'nisn' => '0012345678',
            'gender' => 'male',
            'status' => 'active',
        ]);
        $event = RfidAttendanceEvent::create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'result_code' => 'ATTENDANCE_CREATED',
            'success' => true,
            'message' => 'Absensi berhasil dicatat.',
            'scanned_at' => now(),
        ]);

        $feed = app(LiveAttendanceService::class)->feed(
            $user,
            $classroom->id,
            now()->toDateString(),
            0,
        );

        $this->assertSame($event->id, $feed['events'][0]['id']);
        $this->assertSame('0012345678', $feed['events'][0]['nis']);
    }
}
