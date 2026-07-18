<?php

declare(strict_types=1);

namespace App\Http\Requests\OperationalFinance;

use Illuminate\Foundation\Http\FormRequest;

class StoreBudgetAllocationRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return []; }
    public function attributes(): array { return ['amount' => 'nominal', 'description' => 'uraian']; }
}
