<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

final class PayrollCalculationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payrolls.calculate') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
