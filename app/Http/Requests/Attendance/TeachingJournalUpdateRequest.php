<?php

declare(strict_types=1);

namespace App\Http\Requests\Attendance;

use App\Enums\AttendanceStatus;
use App\Enums\TeachingJournalStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TeachingJournalUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('teaching-journals.create') || $this->user()?->can('teaching-journals.update');
    }

    public function rules(): array
    {
        return [
            'teaching_assignment_id' => ['required', 'exists:teaching_assignments,id'],
            'lesson_schedule_id' => ['nullable', 'exists:lesson_schedules,id'],
            'journal_date' => ['required', 'date'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'lesson_hours' => ['required', 'integer', 'min:1', 'max:12'],
            'material' => ['required', 'string', 'max:5000'],
            'learning_objectives' => ['nullable', 'string', 'max:5000'],
            'method' => ['nullable', 'string', 'max:255'],
            'media' => ['nullable', 'string', 'max:255'],
            'assignment' => ['nullable', 'string', 'max:5000'],
            'assessment' => ['nullable', 'string', 'max:5000'],
            'teacher_notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in([TeachingJournalStatus::Draft->value, TeachingJournalStatus::Submitted->value])],
            'students' => ['nullable', 'array'],
            'students.*.status' => ['required_with:students', Rule::enum(AttendanceStatus::class)],
            'students.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
