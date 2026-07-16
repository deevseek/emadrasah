<?php

declare(strict_types=1);

namespace App\Http\Requests\Attendance;

use App\Enums\AttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StudentAttendanceBulkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('student-attendances.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'attendance_date' => ['required', 'date'],
            'students' => ['required', 'array', 'min:1'],
            'students.*.status' => ['required', Rule::enum(AttendanceStatus::class)],
            'students.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
