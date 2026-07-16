<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class EmployeePayrollItem extends Model
{
    protected $guarded = []; protected $casts=['quantity'=>'decimal:2','rate'=>'decimal:2','amount'=>'decimal:2'];
}
