<?php
declare(strict_types=1); namespace App\Models; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\HasMany;
class PayrollPaymentBatch extends Model { protected $guarded=[]; protected function casts():array{return ['total_amount'=>'decimal:2','approved_at'=>'datetime','executed_at'=>'datetime'];} public function disbursements():HasMany{return $this->hasMany(PayrollDisbursement::class);} }
