<?php

declare(strict_types=1);

namespace App\Enums\Payroll;

enum PayrollComponentType: string { case Earning = 'earning'; case Deduction = 'deduction'; public function label(): string { return $this === self::Earning ? 'Penghasilan' : 'Potongan'; } }
