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
            'snapshots' => ['required', 'array', 'size:5'],
            'snapshots.*' => ['required', 'image', 'mimes:jpeg', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'snapshots.required' => 'Pemindaian wajah wajib dilakukan terlebih dahulu.',
            'snapshots.size' => 'Pemindaian wajah harus berisi lima frame.',
            'snapshots.*.image' => 'Frame wajah harus berupa gambar yang valid.',
            'snapshots.*.mimes' => 'Frame wajah harus menggunakan format JPEG.',
            'snapshots.*.max' => 'Ukuran frame wajah terlalu besar. Silakan coba kembali.',
        ];
    }
}
