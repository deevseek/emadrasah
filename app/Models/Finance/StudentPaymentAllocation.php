<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class StudentPaymentAllocation extends Model
{
    protected $guarded = []; protected $casts=['amount'=>'decimal:2']; public function invoice(){return $this->belongsTo(StudentInvoice::class,'student_invoice_id');}
}
