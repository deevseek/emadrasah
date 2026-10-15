<?php

declare(strict_types=1);

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class ExportAcademicAttendanceReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('academic-reports.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'month' => ['required', 'date_format:Y-m'],
        ];
    }

    public function messages(): array
    {
        return [
            'classroom_id.required' => 'Rombel wajib dipilih untuk membuat laporan absensi.',
            'classroom_id.exists' => 'Rombel yang dipilih tidak ditemukan.',
            'month.required' => 'Bulan laporan wajib dipilih.',
            'month.date_format' => 'Format bulan laporan tidak valid.',
        ];
    }
}
