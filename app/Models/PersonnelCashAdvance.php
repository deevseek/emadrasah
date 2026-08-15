<?php
declare(strict_types=1);
namespace App\Models;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};
class PersonnelCashAdvance extends Model{protected $guarded=[];protected function casts():array{return ['request_date'=>'date','disbursement_date'=>'date','approved_at'=>'datetime','rejected_at'=>'datetime','disbursed_at'=>'datetime','amount'=>'decimal:2','remaining_amount'=>'decimal:2','installment_amount'=>'decimal:2'];}public function personnel():BelongsTo{return $this->belongsTo(Personnel::class);}public function payments():HasMany{return $this->hasMany(PersonnelCashAdvancePayment::class,'cash_advance_id');}}
