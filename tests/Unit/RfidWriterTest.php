<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\RfidCommandStatus;
use App\Services\Rfid\RfidWriterService;
use PHPUnit\Framework\TestCase;

class RfidWriterTest extends TestCase
{
    public function test_card_token_is_random_sixteen_byte_hexadecimal(): void
    {
        $first = RfidWriterService::generateCardToken();
        $second = RfidWriterService::generateCardToken();

        $this->assertMatchesRegularExpression('/^[A-F0-9]{32}$/', $first);
        $this->assertNotSame($first, $second);
        $this->assertSame(16, strlen(hex2bin($first)));
    }

    public function test_command_statuses_cover_terminal_and_work_states(): void
    {
        $this->assertSame(
            ['pending', 'processing', 'completed', 'failed', 'expired'],
            array_column(RfidCommandStatus::cases(), 'value'),
        );
    }
}
