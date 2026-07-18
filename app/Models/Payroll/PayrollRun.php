<?php

declare(strict_types=1);

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollRun extends Model
{
    protected $guarded = [];
    public function period(){return $this->belongsTo(PayrollPeriod::class,'payroll_period_id');} public function items(){return $this->hasMany(PayrollItem::class);} public function payments(){return $this->hasMany(PayrollPayment::class);}
    protected $casts = ['effective_start_date'=>'date','effective_end_date'=>'date','period_start_date'=>'date','period_end_date'=>'date','attendance_cutoff_start'=>'date','attendance_cutoff_end'=>'date','payment_due_date'=>'date','payment_date'=>'date','generated_at'=>'datetime','submitted_at'=>'datetime','approved_at'=>'datetime','rejected_at'=>'datetime','finalized_at'=>'datetime','locked_at'=>'datetime','cancelled_at'=>'datetime','attendance_snapshot'=>'array','snapshot'=>'array','is_active'=>'boolean','show_on_payslip'=>'boolean','is_manual_adjustment'=>'boolean','is_attendance_related'=>'boolean','is_tax_related'=>'boolean','is_fixed'=>'boolean','is_prorated'=>'boolean'];
}
