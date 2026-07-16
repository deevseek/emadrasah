<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeLeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

final 
class EmployeeLeaveTest extends TestCase
{
    use RefreshDatabase;

    private bool $seeded = false;

    public function test_employee_can_create_leave_with_attachment_and_overlap_is_rejected(): void
    {
        Storage::fake('private');
        $employee = $this->employeeUser();

        $payload = [
            'starts_at' => '2026-07-20',
            'ends_at' => '2026-07-21',
            'type' => LeaveType::Sick->value,
            'reason' => 'Sakit dan perlu istirahat.',
            'attachment' => UploadedFile::fake()->create('surat.pdf', 50, 'application/pdf'),
        ];

        $this->actingAs($employee->user)->post(route('employee-leaves.store'), $payload)->assertRedirect();
        $this->assertDatabaseHas('employee_leave_requests', ['employee_id' => $employee->id, 'status' => LeaveStatus::Pending->value]);
        $this->assertDatabaseHas('activity_log', ['event' => 'employee-leave.created']);

        $this->actingAs($employee->user)->post(route('employee-leaves.store'), $payload)->assertSessionHasErrors('starts_at');
    }

    public function test_approve_syncs_attendance_and_reject_requires_reason(): void
    {
        $admin = $this->admin();
        $leave = EmployeeLeaveRequest::query()->create([
            'employee_id' => $this->employeeUser(['employee-leaves.view-own'])->id,
            'starts_at' => '2026-07-22',
            'ends_at' => '2026-07-22',
            'type' => LeaveType::Sick,
            'reason' => 'Sakit.',
            'status' => LeaveStatus::Pending,
        ]);

        $this->actingAs($admin)->patch(route('employee-leaves.reject', $leave))->assertSessionHasErrors('rejection_reason');
        $this->actingAs($admin)->patch(route('employee-leaves.approve', $leave))->assertSessionHas('status');

        $this->assertTrue(
            EmployeeAttendance::query()
                ->where('employee_id', $leave->employee_id)
                ->whereDate('attendance_date', '2026-07-22')
                ->where('status', AttendanceStatus::Sick->value)
                ->exists()
        );
        $this->assertDatabaseHas('activity_log', ['event' => 'employee-leave.approved']);
    }

    private function admin(): User
    {
        $this->seedOnce();

        return User::query()->where('email', 'admin@example.test')->firstOrFail();
    }

    private function employeeUser(array $permissions = [
        'employee-leaves.view-own',
        'employee-leaves.create',
        'employee-leaves.cancel',
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
