<?php

declare(strict_types=1);

namespace App\Http\Requests\ReportCard;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateReportCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('report-cards.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'general_notes' => ['nullable', 'string', 'max:5000'],
            'attendance_notes' => ['nullable', 'string', 'max:5000'],
            'homeroom_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return ['general_notes' => 'catatan umum', 'homeroom_notes' => 'catatan wali kelas'];
    }
}
