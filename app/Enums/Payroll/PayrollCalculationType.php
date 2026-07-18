<?php

declare(strict_types=1);

namespace App\Enums\Payroll;

enum PayrollCalculationType: string { case Fixed='fixed'; case Percentage='percentage'; case Attendance='attendance'; case Manual='manual'; case ControlledFormula='controlled_formula'; public function label(): string { return match($this){self::Fixed=>'Nominal Tetap',self::Percentage=>'Persentase',self::Attendance=>'Berdasarkan Kehadiran',self::Manual=>'Manual',self::ControlledFormula=>'Formula Terkontrol'}; } }
