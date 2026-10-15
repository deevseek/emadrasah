<?php

declare(strict_types=1);

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class ExportAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->routeIs('academic.reports.attendance.export')
            ? 'academic-reports.view'
            : 'academic-attendance.view';

        return $this->user()?->can($permission) ?? false;
    }

    public function rules(): array
    {
        return ['classroom_id' => ['required', 'integer', 'exists:classrooms,id'], 'month' => ['required', 'date_format:Y-m']];
    }

    public function messages(): array
    {
        return [
            'classroom_id.required' => 'Rombel wajib dipilih untuk membuat laporan.',
            'classroom_id.exists' => 'Rombel yang dipilih tidak ditemukan.',
            'month.required' => 'Bulan laporan wajib dipilih.',
            'month.date_format' => 'Format bulan laporan tidak valid.',
        ];
    }
}
