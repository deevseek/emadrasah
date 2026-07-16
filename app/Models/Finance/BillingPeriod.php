<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class BillingPeriod extends Model
{
    protected $guarded = []; protected $casts=['starts_on'=>'date','due_on'=>'date','is_active'=>'boolean'];
}
