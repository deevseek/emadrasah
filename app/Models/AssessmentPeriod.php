<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentPeriod extends Model
{
    protected $guarded = [];
    protected $casts = ['start_at'=>'datetime','end_at'=>'datetime','allow_draft'=>'boolean','allow_submission'=>'boolean','allow_revision'=>'boolean','locked_at'=>'datetime'];
}
