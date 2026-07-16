<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class FinancialTransactionLine extends Model
{
    protected $guarded = []; protected $casts=['debit'=>'decimal:2','credit'=>'decimal:2'];
}
