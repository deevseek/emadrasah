<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class EmployeeAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_without_employee_is_forbidden(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $user->givePermissionTo('employee-attendances.view-own');

        $this->actingAs($user)->get(route('employee-attendances.mine'))->assertForbidden();
    }

    public function test_employee_can_check_in_once_and_check_out_once(): void
    {
        Storage::fake('public');
        $employee = $this->employeeUser();

        $this->actingAs($employee->user)->post(route('employee-attendances.check-in'), [
            'latitude' => '-6.2',
            'longitude' => '106.8',
            'accuracy' => '8',
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ])->assertSessionHas('status');

        $this->assertDatabaseHas('employee_attendances', ['employee_id' => $employee->id]);
        $this->assertGreaterThan(0, ActivityLog::query()->where('event', 'employee-attendance.checked-in')->count());

        $this->actingAs($employee->user)->post(route('employee-attendances.check-in'))->assertSessionHasErrors('attendance');
        $this->actingAs($employee->user)->post(route('employee-attendances.check-out'))->assertSessionHas('status');
        $this->actingAs($employee->user)->post(route('employee-attendances.check-out'))->assertSessionHasErrors('attendance');
    }

    public function test_admin_can_verify_and_correct_attendance(): void
    {
        $admin = $this->admin();
        $attendance = EmployeeAttendance::query()->create([
            'employee_id' => Employee::query()->firstOrFail()->id,
            'attendance_date' => now()->toDateString(),
            'status' => AttendanceStatus::Present,
        ]);

        $this->actingAs($admin)->patch(route('employee-attendances.verify', $attendance), [
            'status' => AttendanceStatus::Late->value,
            'correction_reason' => 'Koreksi hasil verifikasi lokasi.',
        ])->assertSessionHas('status');

        $this->assertDatabaseHas('employee_attendances', [
            'id' => $attendance->id,
            'status' => AttendanceStatus::Late->value,
            'is_verified' => true,
        ]);
        $this->assertDatabaseHas('activity_log', ['event' => 'employee-attendance.verified']);
    }

    private function admin(): User
    {
        $this->seed();

        return User::query()->where('email', 'admin@example.test')->firstOrFail();
    }

    private function employeeUser(): Employee
    {
        $this->seed();
        $employee = Employee::query()->whereNotNull('user_id')->firstOrFail();
        $employee->user->givePermissionTo([
            'employee-attendances.view-own',
            'employee-attendances.check-in',
            'employee-attendances.check-out',
        ]);

        return $employee;
    }
}
