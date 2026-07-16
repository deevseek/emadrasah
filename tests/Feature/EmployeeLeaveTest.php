<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\Employee;
use App\Models\EmployeeLeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class EmployeeLeaveTest extends TestCase
{
    use RefreshDatabase;

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
            'employee_id' => Employee::query()->firstOrFail()->id,
            'starts_at' => '2026-07-22',
            'ends_at' => '2026-07-22',
            'type' => LeaveType::Sick,
            'reason' => 'Sakit.',
            'status' => LeaveStatus::Pending,
        ]);

        $this->actingAs($admin)->patch(route('employee-leaves.reject', $leave))->assertSessionHasErrors('rejection_reason');
        $this->actingAs($admin)->patch(route('employee-leaves.approve', $leave))->assertSessionHas('status');

        $this->assertDatabaseHas('employee_attendances', [
            'employee_id' => $leave->employee_id,
            'attendance_date' => '2026-07-22',
            'status' => 'sakit',
        ]);
        $this->assertDatabaseHas('activity_log', ['event' => 'employee-leave.approved']);
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
        $employee->user->givePermissionTo(['employee-leaves.view-own', 'employee-leaves.create', 'employee-leaves.cancel']);

        return $employee;
    }
}
