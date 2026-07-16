<?php

declare(strict_types=1);

namespace App\Http\Requests\Btaq;

use App\Enums\AttendanceStatus;
use App\Enums\BtaqJournalStatus;
use App\Enums\BtaqProgressStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBtaqJournalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('btaq-journals.create') ?? false;
    }

    public function rules(): array
    {
        return $this->baseRules();
    }

    public function attributes(): array
    {
        return ['btaq_group_id' => 'kelompok BTAQ', 'journal_date' => 'tanggal jurnal', 'session_number' => 'sesi'];
    }

    protected function baseRules(): array
    {
        return [
            'btaq_group_id' => ['required', 'exists:btaq_groups,id'],
            'journal_date' => ['required', 'date'],
            'session_number' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i', 'after:starts_at'],
            'btaq_material_id' => ['nullable', 'exists:btaq_materials,id'],
            'general_notes' => ['nullable', 'string'],
            'status' => ['nullable', Rule::enum(BtaqJournalStatus::class)],
            'students' => ['nullable', 'array'],
            'students.*.attendance_status' => ['nullable', Rule::enum(AttendanceStatus::class)],
            'students.*.progress_status' => ['nullable', Rule::enum(BtaqProgressStatus::class)],
            'students.*.reading_score' => ['nullable', 'numeric', 'between:0,100'],
            'students.*.memorization_score' => ['nullable', 'numeric', 'between:0,100'],
            'students.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
