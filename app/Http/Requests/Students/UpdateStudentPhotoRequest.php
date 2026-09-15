<?php

declare(strict_types=1);

namespace App\Http\Requests\Students;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateStudentPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('students.photo.manage');
    }

    public function rules(): array
    {
        return ['photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120'];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (blank($this->route('student')->nisn)) {
                $validator->errors()->add('photo', 'NISN siswa wajib diisi sebelum foto dapat disimpan.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'Foto siswa wajib diambil atau dipilih.',
            'photo.image' => 'Foto siswa harus berupa gambar.',
            'photo.mimes' => 'Foto siswa harus berformat JPG, JPEG, PNG, atau WEBP.',
            'photo.max' => 'Ukuran foto siswa maksimal 5 MB.',
        ];
    }
}
