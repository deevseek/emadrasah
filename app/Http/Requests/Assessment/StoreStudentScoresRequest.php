<?php

declare(strict_types=1);

namespace App\Http\Requests\Assessment;

use Illuminate\Foundation\Http\FormRequest;

final class StoreStudentScoresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assessments.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.score' => ['nullable', 'numeric', 'min:0'],
            'scores.*.remedial_score' => ['nullable', 'numeric', 'min:0'],
            'scores.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return ['scores' => 'nilai siswa', 'scores.*.score' => 'nilai', 'scores.*.remedial_score' => 'nilai remedial'];
    }
}
