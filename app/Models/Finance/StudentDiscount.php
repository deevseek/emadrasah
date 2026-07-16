<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class StudentDiscount extends Model
{
    protected $guarded = []; protected $casts=['starts_on'=>'date','ends_on'=>'date','discount_value'=>'decimal:2','maximum_discount'=>'decimal:2'];
}
