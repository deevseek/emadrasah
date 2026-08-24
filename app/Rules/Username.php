<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Username implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Username harus berupa teks.');

            return;
        }

        $isConventionalUsername = preg_match('/^[a-z0-9._-]+$/', $value) === 1;
        $isEmailAddress = filter_var($value, FILTER_VALIDATE_EMAIL) !== false;

        if (! $isConventionalUsername && ! $isEmailAddress) {
            $fail('Username harus berupa alamat email yang valid atau hanya berisi huruf kecil, angka, titik, garis bawah, dan tanda minus.');
        }
    }
}
