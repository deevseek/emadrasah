<?php

declare(strict_types=1);

namespace App\Http\Requests\Pmbm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['payment_stage' => ['required', Rule::in(['initial', 'registration'])], 'amount' => 'required|numeric|min:1|max:999999999999.99', 'payment_method' => 'required|string|max:40', 'reference_number' => 'nullable|string|max:100', 'paid_at' => 'required|date|before_or_equal:now', 'proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120'];
    }
}
