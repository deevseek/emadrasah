<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class SalaryComponent extends Model
{
    protected $guarded = []; protected $casts=['default_amount'=>'decimal:2','percentage'=>'decimal:4','taxable'=>'boolean','is_attendance_based'=>'boolean','is_active'=>'boolean'];
}
