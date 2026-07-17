<?php

declare(strict_types=1);

namespace App\Http\Requests\Btaq;

use Illuminate\Foundation\Http\FormRequest;

final class TransferBtaqStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return ['name' => 'nama', 'code' => 'kode', 'rejection_reason' => 'alasan perbaikan'];
    }
}
