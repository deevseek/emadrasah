<?php

declare(strict_types=1);

namespace App\Models\OperationalFinance;

use App\Models\Finance\CashAccount;
use Illuminate\Database\Eloquent\Model;

class CashTransfer extends Model { protected $guarded=[]; protected $casts=['transfer_date'=>'date','posted_at'=>'datetime']; public function sourceCashAccount(){return $this->belongsTo(CashAccount::class,'source_cash_account_id');} public function destinationCashAccount(){return $this->belongsTo(CashAccount::class,'destination_cash_account_id');} public function outTransaction(){return $this->belongsTo(OperationalTransaction::class,'out_transaction_id');} public function inTransaction(){return $this->belongsTo(OperationalTransaction::class,'in_transaction_id');} }
