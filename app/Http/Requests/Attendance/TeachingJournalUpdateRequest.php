<?php

declare(strict_types=1);

namespace App\Http\Requests\Attendance;

use App\Enums\TeachingJournalStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TeachingJournalUpdateRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('teaching-journals.create') ?? false; }
    public function rules(): array
    {
        return [
            'lesson_schedule_id' => ['required','integer','exists:lesson_schedules,id'],
            'journal_date' => ['required','date','before_or_equal:today'],
            'learning_topic' => ['required','string','max:255'],
            'learning_objectives' => ['required','string'],
            'learning_material' => ['required','string'],
            'learning_method' => ['nullable','string','max:255'],
            'learning_media' => ['nullable','string','max:255'],
            'learning_activity' => ['required','string'],
            'assessment_activity' => ['nullable','string'],
            'homework' => ['nullable','string'],
            'teacher_notes' => ['nullable','string'],
            'obstacles' => ['nullable','string'],
            'follow_up' => ['nullable','string'],
            'status' => ['required', Rule::in([TeachingJournalStatus::Draft->value, TeachingJournalStatus::Submitted->value])],
        ];
    }
    public function attributes(): array { return ['lesson_schedule_id'=>'jadwal mengajar','journal_date'=>'tanggal jurnal','learning_topic'=>'topik pembelajaran','learning_objectives'=>'tujuan pembelajaran','learning_material'=>'materi pembelajaran','learning_activity'=>'kegiatan pembelajaran']; }
}
