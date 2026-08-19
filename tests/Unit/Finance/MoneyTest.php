<?php

declare(strict_types=1);

namespace Tests\Unit\Finance;

use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    #[DataProvider('amounts')]
    public function test_formats_money_without_float(string|int $input, string $expected): void
    {
        self::assertSame($expected, Money::decimal($input));
    }

    public static function amounts(): array { return [[350000, '350000.00'], ['0001.5', '1.50'], ['0.01', '0.01']]; }

    public function test_rejects_more_than_two_decimals(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::decimal('1.001');
    }
}
