<?php

declare(strict_types=1);
namespace App\Models\Finance;
use Illuminate\Database\Eloquent\Model;
class PaymentReceiptDelivery extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['sent_at'=>'datetime','failed_at'=>'datetime','attempt_count'=>'integer']; }
    public function payment() { return $this->belongsTo(StudentPayment::class, 'student_payment_id'); }
}
