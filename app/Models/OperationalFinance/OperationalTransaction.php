<?php

declare(strict_types=1);

namespace App\Models\OperationalFinance;

use App\Models\Finance\CashAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OperationalTransaction extends Model { protected $guarded=[]; protected $casts=['transaction_date'=>'date','submitted_at'=>'datetime','approved_at'=>'datetime','rejected_at'=>'datetime','cancelled_at'=>'datetime','posted_at'=>'datetime']; public function cashAccount(){return $this->belongsTo(CashAccount::class);} public function category(){return $this->belongsTo(FinanceCategory::class,'finance_category_id');} public function attachments(){return $this->hasMany(TransactionAttachment::class);} public function approvals(){return $this->hasMany(TransactionApproval::class);} public function creator(){return $this->belongsTo(User::class,'created_by');} }
