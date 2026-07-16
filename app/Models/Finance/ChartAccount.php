<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class ChartAccount extends Model
{
    protected $guarded = []; public function lines(){return $this->hasMany(FinancialTransactionLine::class);}
}
