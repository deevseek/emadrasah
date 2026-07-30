<?php

declare(strict_types=1);

namespace App\Http\Requests\Foundation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class AcademicPeriodRequest extends FormRequest
{
    abstract protected function permission(): string;

    public function authorize(): bool { return $this->user()?->can($this->permission()) === true; }

    public function rules(): array
    {
        $id = $this->route('academicYear')?->getKey();
        return [
            'name' => ['required', 'string', 'max:9', 'regex:/^\d{4}\/\d{4}$/', Rule::unique('academic_years', 'name')->ignore($id)],
            'starts_at' => ['required', 'date', 'before:ends_at'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'odd_starts_at' => ['required', 'date', 'before:odd_ends_at'],
            'odd_ends_at' => ['required', 'date', 'after:odd_starts_at', 'before:even_starts_at'],
            'even_starts_at' => ['required', 'date', 'after:odd_ends_at', 'before:even_ends_at'],
            'even_ends_at' => ['required', 'date', 'after:even_starts_at'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (preg_match('/^(\d{4})\/(\d{4})$/', (string) $this->input('name'), $parts) && (int) $parts[2] !== (int) $parts[1] + 1) {
                $validator->errors()->add('name', 'Tahun kedua harus satu tahun setelah tahun pertama.');
            }
            try {
                $yearStart = new \DateTimeImmutable((string) $this->input('starts_at'));
                $yearEnd = new \DateTimeImmutable((string) $this->input('ends_at'));
                foreach (['odd_starts_at', 'odd_ends_at', 'even_starts_at', 'even_ends_at'] as $field) {
                    $date = new \DateTimeImmutable((string) $this->input($field));
                    if ($date < $yearStart || $date > $yearEnd) {
                        $validator->errors()->add($field, 'Tanggal semester harus berada dalam rentang tahun ajaran.');
                    }
                }
                if (new \DateTimeImmutable((string) $this->input('odd_ends_at')) >= new \DateTimeImmutable((string) $this->input('even_starts_at'))) {
                    $validator->errors()->add('even_starts_at', 'Semester Ganjil dan Semester Genap tidak boleh bertabrakan.');
                    $validator->errors()->add('even_starts_at', 'Semester Genap harus dimulai setelah Semester Ganjil selesai.');
                }
            } catch (\Throwable) {
                // Aturan date standar memberikan pesan yang tepat untuk nilai tanggal tidak valid.
            }
        }];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Format tahun ajaran harus seperti 2026/2027.',
            'name.unique' => 'Tahun ajaran sudah tersedia.',
            'ends_at.after' => 'Tanggal selesai harus setelah tanggal mulai.',
            '*.after' => 'Tanggal selesai semester harus setelah tanggal mulai.',
            '*.before' => 'Tanggal selesai semester harus setelah tanggal mulai.',
            '*.required' => ':attribute wajib diisi.',
            '*.date' => ':attribute harus berupa tanggal yang valid.',
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'Nama tahun ajaran', 'starts_at' => 'Tanggal mulai', 'ends_at' => 'Tanggal selesai', 'odd_starts_at' => 'Tanggal mulai Semester Ganjil', 'odd_ends_at' => 'Tanggal selesai Semester Ganjil', 'even_starts_at' => 'Tanggal mulai Semester Genap', 'even_ends_at' => 'Tanggal selesai Semester Genap'];
    }
}
