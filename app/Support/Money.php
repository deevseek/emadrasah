<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final class Money
{
    public static function idr(string|int $value): array
    {
        return ['value' => self::decimal($value), 'currency' => 'IDR'];
    }

    public static function decimal(string|int $value): string
    {
        $value = trim((string) $value);
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            throw new InvalidArgumentException('Nominal uang tidak valid.');
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        $whole = ltrim($whole, '0');

        return ($whole === '' ? '0' : $whole).'.'.str_pad($fraction, 2, '0');
    }
}
