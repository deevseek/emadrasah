<?php

declare(strict_types=1);

namespace App\Http\Requests\TeachingAssignments;

use Illuminate\Foundation\Http\FormRequest;

class UploadTeachingAssignmentRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('teaching-assignments.import'); }
    public function rules(): array { return ['academic_year_id' => ['required', 'exists:academic_years,id'], 'file' => ['required', 'file', 'mimes:xlsx', 'max:20480']]; }
    public function messages(): array { return ['academic_year_id.required' => 'Tahun ajaran wajib dipilih.', 'file.mimes' => 'File wajib berformat XLSX.']; }
}
