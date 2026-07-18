<?php

declare(strict_types=1);

namespace App\Enums\Payroll;

enum PayrollPaymentStatus: string { case Unpaid='unpaid'; case Partial='partial'; case Paid='paid'; case Cancelled='cancelled'; public function label(): string { return match($this){self::Unpaid=>'Belum Dibayar',self::Partial=>'Dibayar Sebagian',self::Paid=>'Dibayar',self::Cancelled=>'Dibatalkan'}; } }
