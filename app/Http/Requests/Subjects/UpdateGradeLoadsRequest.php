<?php

declare(strict_types=1);

namespace App\Http\Requests\Subjects;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGradeLoadsRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('subjects.manage-loads'); }
    public function rules(): array { return ['loads' => ['array'], 'loads.*' => ['nullable', 'integer', 'between:1,99']]; }
    public function messages(): array { return ['loads.*.min' => 'JP harus lebih dari 0. Kosongkan kolom jika mata pelajaran tidak digunakan.']; }
}
