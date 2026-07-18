<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class CashAccount extends Model
{
    protected $guarded = []; public function chartAccount(){return $this->belongsTo(ChartAccount::class);} public function operationalTransactions(){return $this->hasMany(\App\Models\OperationalFinance\OperationalTransaction::class);} public function operationalClosings(){return $this->hasMany(\App\Models\OperationalFinance\CashClosing::class);}
}
