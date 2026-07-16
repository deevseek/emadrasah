<?php

declare(strict_types=1);

namespace App\Enums\Finance;

enum BillingFrequency: string { case Once='sekali'; case Monthly='bulanan'; case Semester='semester'; case Yearly='tahunan'; case Incidental='insidental'; }
