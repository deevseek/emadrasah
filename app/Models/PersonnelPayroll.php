<?php
declare(strict_types=1);
namespace App\Models;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};
class PersonnelPayroll extends Model{protected $guarded=[];protected function casts():array{return ['period_start'=>'date','period_end'=>'date','pay_date'=>'date','monthly_salary'=>'decimal:2','base_salary'=>'decimal:2','daily_salary'=>'decimal:2','allowance'=>'decimal:2','deduction'=>'decimal:2','late_deduction'=>'decimal:2','cash_advance_deduction'=>'decimal:2','total'=>'decimal:2'];}public function personnel():BelongsTo{return $this->belongsTo(Personnel::class);}public function cashAdvancePayments():HasMany{return $this->hasMany(PersonnelCashAdvancePayment::class,'payroll_id');}}
