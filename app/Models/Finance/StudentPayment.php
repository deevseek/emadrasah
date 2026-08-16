<?php

declare(strict_types=1);
namespace App\Models\Finance;
use Illuminate\Database\Eloquent\Model;
class StudentPayment extends Model
{
    protected $table='student_payments'; protected $guarded=[];
    protected function casts():array{return ['amount'=>'decimal:2','paid_at'=>'datetime','metadata'=>'array','receipt_snapshot'=>'array','receipt_generated_at'=>'datetime'];}
    public function student(){return $this->belongsTo(\App\Models\Student::class);} public function invoice(){return $this->belongsTo(StudentInvoice::class);} public function creator(){return $this->belongsTo(\App\Models\User::class,'created_by');}
    public function receiptDeliveries(){return $this->hasMany(PaymentReceiptDelivery::class,'student_payment_id');}
}
