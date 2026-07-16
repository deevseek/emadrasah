<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class StudentInvoice extends Model
{
    protected $guarded = []; protected $casts=['due_on'=>'date','original_amount'=>'decimal:2','discount_amount'=>'decimal:2','penalty_amount'=>'decimal:2','final_amount'=>'decimal:2','paid_amount'=>'decimal:2','outstanding_amount'=>'decimal:2']; public function student(){return $this->belongsTo(\App\Models\Student::class);} public function feeType(){return $this->belongsTo(FeeType::class);} public function allocations(){return $this->hasMany(StudentPaymentAllocation::class);} 
}
