<?php

declare(strict_types=1);

namespace App\Http\Requests\Pmbm;

use App\Enums\Pmbm\ApplicantStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'source' => ['nullable', Rule::in(['online', 'offline'])],
            'status' => ['nullable', Rule::enum(ApplicantStatus::class)],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
        ];
    }

    public function attributes(): array
    {
        return ['academic_year_id' => 'tahun pelajaran', 'date_from' => 'tanggal mulai', 'date_to' => 'tanggal akhir', 'source' => 'sumber pendaftaran', 'status' => 'status pendaftaran', 'gender' => 'jenis kelamin'];
    }
}
