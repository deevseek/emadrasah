<?php

declare(strict_types=1);

namespace App\Enums\Finance;

enum TransactionStatus: string { case Draft='draft'; case Posted='posted'; case Cancelled='cancelled'; }
