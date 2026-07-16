<?php

declare(strict_types=1);

namespace App\Enums\Finance;

enum InvoiceStatus: string { case Draft='draft'; case Unpaid='unpaid'; case PartiallyPaid='partially_paid'; case Paid='paid'; case Overdue='overdue'; case Cancelled='cancelled'; }
