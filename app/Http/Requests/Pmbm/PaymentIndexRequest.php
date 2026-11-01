<?php

declare(strict_types=1);

namespace App\Http\Requests\Pmbm;

use App\Enums\Pmbm\{PaymentStage, PaymentStatus};
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentIndexRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:100',
            'academic_year_id' => 'nullable|integer|exists:academic_years,id',
            'status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'payment_stage' => ['nullable', Rule::enum(PaymentStage::class)],
            'from' => 'nullable|date_format:Y-m-d',
            'until' => 'nullable|date_format:Y-m-d|after_or_equal:from',
        ];
    }
}
