<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class EmployeeSalaryComponent extends Model
{
    protected $guarded = []; protected $casts=['effective_from'=>'date','effective_until'=>'date','amount'=>'decimal:2','percentage'=>'decimal:4','is_active'=>'boolean'];
}
