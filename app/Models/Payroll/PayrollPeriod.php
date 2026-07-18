<?php

declare(strict_types=1);

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model
{
    protected $table = 'payroll_periods_v2';
    protected $guarded = [];
    protected $casts = ['period_start_date'=>'date','period_end_date'=>'date','attendance_cutoff_start'=>'date','attendance_cutoff_end'=>'date','payment_due_date'=>'date','locked_at'=>'datetime'];
    public function runs(){return $this->hasMany(PayrollRun::class);}
}
