<?php

declare(strict_types=1);

namespace App\Http\Requests\Assessment;

use App\Enums\AssessmentStatus;
use App\Enums\AssessmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAssessmentComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assessments.create') ?? false;
    }
    public function rules(): array
    {
        return [
            'teaching_assignment_id' => ['required', 'exists:teaching_assignments,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(AssessmentType::class)],
            'weight' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'maximum_score' => ['required', 'numeric', 'min:1', 'max:1000'],
            'assessment_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'is_required' => ['boolean'],
            'status' => ['nullable', Rule::enum(AssessmentStatus::class)],
        ];
    }
    protected function prepareForValidation(): void
    {
        $this->merge(['is_required' => $this->boolean('is_required')]);
    }
    public function attributes(): array
    {
        return [
            'teaching_assignment_id' => 'penugasan mengajar',
            'name' => 'nama komponen',
            'type' => 'jenis penilaian',
            'weight' => 'bobot',
            'maximum_score' => 'skor maksimum',
        ];
    }
}
