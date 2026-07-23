<?php

declare(strict_types=1);

namespace App\Http\Requests\StudentAffairs;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteStudentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('students.delete') ?? false;
    }

    public function rules(): array
    {
        return [
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'distinct', 'exists:students,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'student_ids' => 'siswa yang dipilih',
            'student_ids.*' => 'siswa yang dipilih',
        ];
    }
}
