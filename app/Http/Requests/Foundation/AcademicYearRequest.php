<?php

declare(strict_types=1);

namespace App\Http\Requests\Foundation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademicYearRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can($this->route('academic_year') ? 'academic-years.update' : 'academic-years.create') ?? false; }
    public function rules(): array
    {
        $id = $this->route('academic_year')?->id;
        return [
            'name' => ['required', 'string', 'max:32', Rule::unique('academic_years', 'name')->ignore($id)],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
        ];
    }
    public function messages(): array { return ['name.required' => 'Nama tahun ajaran wajib diisi.', 'name.unique' => 'Nama tahun ajaran sudah digunakan.', 'ends_on.after' => 'Tanggal selesai harus setelah tanggal mulai.']; }
}
