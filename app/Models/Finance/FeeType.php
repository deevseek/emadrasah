<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class FeeType extends Model
{
    protected $guarded = []; protected $casts=['is_active'=>'boolean','is_mandatory'=>'boolean','default_amount'=>'decimal:2']; public function invoices(){return $this->hasMany(StudentInvoice::class);}
}
