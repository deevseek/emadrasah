<?php

declare(strict_types=1);

namespace App\Http\Requests\Hrd;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class HrdReportFilterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'start_date' => $this->input('start_date', now()->startOfMonth()->toDateString()),
            'end_date' => $this->input('end_date', now()->endOfMonth()->toDateString()),
        ]);
    }

    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }

    public function period(): array
    {
        $data = $this->validated();

        return [
            CarbonImmutable::createFromFormat('!Y-m-d', $data['start_date']),
            CarbonImmutable::createFromFormat('!Y-m-d', $data['end_date']),
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.date_format' => 'Tanggal awal harus menggunakan format tanggal yang valid.',
            'end_date.date_format' => 'Tanggal akhir harus menggunakan format tanggal yang valid.',
            'end_date.after_or_equal' => 'Tanggal akhir tidak boleh lebih awal daripada tanggal awal.',
        ];
    }
}
