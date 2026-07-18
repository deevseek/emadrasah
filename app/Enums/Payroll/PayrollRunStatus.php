<?php

declare(strict_types=1);

namespace App\Enums\Payroll;

enum PayrollRunStatus: string { case Draft='draft'; case Open='open'; case Calculated='calculated'; case Submitted='submitted'; case Revision='revision'; case Approved='approved'; case Final='final'; case PartiallyPaid='partially_paid'; case Paid='paid'; case Cancelled='cancelled'; public function label(): string { return match($this){self::Draft=>'Draft',self::Open=>'Dibuka',self::Calculated=>'Dihitung',self::Submitted=>'Menunggu Persetujuan',self::Revision=>'Perlu Perbaikan',self::Approved=>'Disetujui',self::Final=>'Final',self::PartiallyPaid=>'Dibayar Sebagian',self::Paid=>'Dibayar',self::Cancelled=>'Dibatalkan'}; } }
