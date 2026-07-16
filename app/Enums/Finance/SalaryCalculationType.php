<?php

declare(strict_types=1);

namespace App\Enums\Finance;

enum SalaryCalculationType: string { case Fixed='fixed'; case Percentage='percentage'; case Attendance='attendance'; case Manual='manual'; }
