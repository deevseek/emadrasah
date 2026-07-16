<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportCardStatusHistory extends Model
{
    protected $fillable = [
        'report_card_id',
        'from_status',
        'to_status',
        'reason',
        'changed_by',
    ];
}
