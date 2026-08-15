<?php
declare(strict_types=1);
namespace App\Models;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PersonnelCashAdvancePayment extends Model{protected $guarded=[];protected function casts():array{return ['payment_date'=>'date','amount'=>'decimal:2'];}public function cashAdvance():BelongsTo{return $this->belongsTo(PersonnelCashAdvance::class,'cash_advance_id');}public function payroll():BelongsTo{return $this->belongsTo(PersonnelPayroll::class);}}
