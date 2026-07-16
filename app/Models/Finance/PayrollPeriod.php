<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model
{
    protected $guarded = []; protected $casts=['starts_on'=>'date','ends_on'=>'date','payment_date'=>'date']; public function payrolls(){return $this->hasMany(EmployeePayroll::class);}
}
