<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class FinancialTransaction extends Model
{
    protected $guarded = []; protected $casts=['transaction_date'=>'date','posted_at'=>'datetime','cancelled_at'=>'datetime']; public function lines(){return $this->hasMany(FinancialTransactionLine::class);} 
}
