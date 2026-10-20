<?php

declare(strict_types=1);

namespace App\Http\Requests\Classrooms;

use Illuminate\Foundation\Http\FormRequest;

class SwapClassroomHomeroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('classrooms.assign-homeroom');
    }

    public function rules(): array
    {
        return ['target_classroom_id' => ['required', 'integer', 'exists:classrooms,id']];
    }

    public function messages(): array
    {
        return ['target_classroom_id.required' => 'Pilih rombel untuk ditukar wali kelasnya.'];
    }
}
