<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

final class PayrollApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = match (true) {
            $this->routeIs('finance.payroll-periods.review') => 'payrolls.review',
            $this->routeIs('finance.payroll-periods.approve') => 'payrolls.approve',
            $this->routeIs('finance.payroll-periods.close') => 'payrolls.close',
            default => null,
        };

        return $permission !== null
            && ($this->user()?->can($permission) ?? false);
    }

    public function rules(): array
    {
        return [];
    }
}
