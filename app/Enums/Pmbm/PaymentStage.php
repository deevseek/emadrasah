<?php

declare(strict_types=1);

namespace App\Enums\Pmbm;

enum PaymentStage: string
{
    case Initial = 'initial';
    case Registration = 'registration';

    public function label(): string
    {
        return match ($this) {
            self::Initial => 'Pembayaran awal',
            self::Registration => 'Daftar ulang',
        };
    }
}
