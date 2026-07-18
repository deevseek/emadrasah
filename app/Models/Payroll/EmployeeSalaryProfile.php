<?php

declare(strict_types=1);

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeSalaryProfile extends Model
{
    protected $guarded = [];
    public function employee(){return $this->belongsTo(\App\Models\Employee::class);} public function components(){return $this->hasMany(EmployeeSalaryComponent::class);}
    protected $casts = ['bank_name'=>'encrypted','bank_account_number'=>'encrypted','bank_account_holder'=>'encrypted','effective_start_date'=>'date','effective_end_date'=>'date','period_start_date'=>'date','period_end_date'=>'date','attendance_cutoff_start'=>'date','attendance_cutoff_end'=>'date','payment_due_date'=>'date','payment_date'=>'date','generated_at'=>'datetime','submitted_at'=>'datetime','approved_at'=>'datetime','rejected_at'=>'datetime','finalized_at'=>'datetime','locked_at'=>'datetime','cancelled_at'=>'datetime','attendance_snapshot'=>'array','snapshot'=>'array','is_active'=>'boolean','show_on_payslip'=>'boolean','is_manual_adjustment'=>'boolean','is_attendance_related'=>'boolean','is_tax_related'=>'boolean','is_fixed'=>'boolean','is_prorated'=>'boolean'];
}
