<?php

declare(strict_types=1);

namespace App\Http\Requests\Hrd;

use Illuminate\Foundation\Http\FormRequest;

class VerifyAttendanceFaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('personnel-attendance.check') === true;
    }

    public function rules(): array
    {
        return [
            'challenge_id' => ['required', 'uuid'],
            'nonce' => ['required', 'string', 'size:64'],
            'device_uuid' => ['nullable', 'uuid'],
            'snapshot' => ['required', 'image', 'mimes:jpeg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'snapshot.required' => 'Foto wajah wajib diambil terlebih dahulu.',
            'snapshot.image' => 'Foto wajah harus berupa berkas gambar yang valid.',
            'snapshot.mimes' => 'Foto wajah harus menggunakan format JPEG atau PNG.',
            'snapshot.max' => 'Ukuran foto wajah terlalu besar. Ambil ulang foto dan coba kembali.',
        ];
    }
}
