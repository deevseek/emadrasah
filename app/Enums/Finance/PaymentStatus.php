<?php

declare(strict_types=1);

namespace App\Enums\Finance;

enum PaymentStatus: string { case Posted='posted'; case Cancelled='cancelled'; case Refunded='refunded'; }
