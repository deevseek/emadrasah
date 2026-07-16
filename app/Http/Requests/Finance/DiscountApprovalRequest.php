<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

final class DiscountApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('student-discounts.approve') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
