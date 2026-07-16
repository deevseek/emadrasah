<?php

declare(strict_types=1);

namespace App\Http\Requests\Assessment;

use Illuminate\Foundation\Http\FormRequest;

final class PredicateRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('predicate-ranges.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'ranges' => ['required', 'array', 'min:1'],
            'ranges.*.code' => ['required', 'string', 'max:10'],
            'ranges.*.description' => ['nullable', 'string', 'max:255'],
            'ranges.*.minimum_score' => ['required', 'numeric', 'between:0,100'],
            'ranges.*.maximum_score' => ['required', 'numeric', 'between:0,100'],
            'ranges.*.sequence' => ['required', 'integer', 'min:1'],
            'ranges.*.is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['ranges' => 'rentang predikat'];
    }
}
