<?php

declare(strict_types=1);

namespace App\Http\Requests\Foundation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SemesterRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can($this->route('semester') ? 'semesters.update' : 'semesters.create') ?? false; }
    public function rules(): array
    {
        $id = $this->route('semester')?->id;
        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'term' => ['required', 'integer', Rule::in([1, 2]), Rule::unique('semesters')->where('academic_year_id', $this->input('academic_year_id'))->ignore($id)],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
        ];
    }
    public function messages(): array { return ['term.unique' => 'Semester untuk tahun ajaran tersebut sudah ada.', 'ends_on.after' => 'Tanggal selesai harus setelah tanggal mulai.']; }
}
