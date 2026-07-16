<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class EmployeePayroll extends Model
{
    protected $guarded = []; protected $casts=['calculated_at'=>'datetime','reviewed_at'=>'datetime','approved_at'=>'datetime','paid_at'=>'datetime','net_salary'=>'decimal:2','total_earnings'=>'decimal:2','total_deductions'=>'decimal:2']; public function items(){return $this->hasMany(EmployeePayrollItem::class);} public function period(){return $this->belongsTo(PayrollPeriod::class,'payroll_period_id');}
}
