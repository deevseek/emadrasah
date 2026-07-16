<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Models\Classroom;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final 
class StudentAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_create_is_idempotent_and_rejects_student_from_other_class(): void
    {
        $admin = $this->admin();
        $classroom = Classroom::query()->firstOrFail();
        $enrollment = StudentEnrollment::query()->where('classroom_id', $classroom->id)->firstOrFail();
        $otherEnrollment = StudentEnrollment::query()->where('classroom_id', '!=', $classroom->id)->first();

        $payload = [
            'classroom_id' => $classroom->id,
            'attendance_date' => '2026-07-23',
            'students' => [
                $enrollment->student_id => ['status' => AttendanceStatus::Present->value, 'notes' => 'Tepat waktu'],
            ],
        ];

        $this->actingAs($admin)->post(route('student-attendances.store'), $payload)->assertRedirect();
        $this->assertTrue(
            StudentAttendance::query()
                ->where('student_id', $enrollment->student_id)
                ->whereDate('attendance_date', '2026-07-23')
                ->where('status', AttendanceStatus::Present->value)
                ->exists()
        );

        $payload['students'][$enrollment->student_id]['status'] = AttendanceStatus::Late->value;
        $this->actingAs($admin)->post(route('student-attendances.store'), $payload)->assertRedirect();
        $this->assertSame(
            1,
            StudentAttendance::query()
                ->where('student_id', $enrollment->student_id)
                ->whereDate('attendance_date', '2026-07-23')
                ->count()
        );
        $this->assertTrue(
            StudentAttendance::query()
                ->where('student_id', $enrollment->student_id)
                ->whereDate('attendance_date', '2026-07-23')
                ->where('status', AttendanceStatus::Late->value)
                ->exists()
        );
        $this->assertDatabaseHas('activity_log', ['event' => 'student-attendance.saved']);

        if ($otherEnrollment !== null) {
            $payload['students'][$otherEnrollment->student_id] = ['status' => AttendanceStatus::Alpha->value];
            $this->actingAs($admin)->post(route('student-attendances.store'), $payload)->assertSessionHasErrors('students');
        }
    }

    public function test_permission_required_for_student_attendance(): void
    {
        $this->seed();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('student-attendances.index'))->assertForbidden();
    }

    private function admin(): User
    {
        $this->seed();

        return User::query()->where('email', 'admin@example.test')->firstOrFail();
    }
}
