<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

final class EmployeeAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private bool $seeded = false;

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
            'employee_id' => $this->employeeUser(['employee-attendances.view'])->id,
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
        $this->seedOnce();

        return User::query()->where('email', 'admin@example.test')->firstOrFail();
    }

    private function employeeUser(array $permissions = [
        'employee-attendances.view-own',
        'employee-attendances.check-in',
        'employee-attendances.check-out',
    ]): Employee
    {
        $this->seedOnce();

        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $employee = Employee::query()->create([
            'user_id' => $user->id,
            'employee_number' => 'TEST-'.Str::upper(Str::random(8)),
            'name' => 'Guru Pengujian',
            'gender' => Gender::Male,
            'employment_type' => EmploymentType::ClassTeacher,
            'employee_status' => EmployeeStatus::Permanent,
            'is_active' => true,
        ]);

        $user->givePermissionTo($permissions);

        return $employee->fresh('user');
    }

    private function seedOnce(): void
    {
        if ($this->seeded) {
            return;
        }

        $this->seed();
        $this->seeded = true;
    }
}
