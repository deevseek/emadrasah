<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportStudentCardsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('students.export');
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:200'],
            'gender' => ['nullable', Rule::in(array_keys(config('students.genders')))],
            'status' => ['nullable', Rule::in(array_keys(config('students.statuses')))],
            'classroom_label' => ['nullable', 'string', 'max:200'],
            'special_needs' => ['nullable', Rule::in(['yes', 'no'])],
            'kip_pip' => ['nullable', Rule::in(['yes', 'no'])],
        ];
    }
}
