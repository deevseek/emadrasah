<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = ['user_id','event','auditable_type','auditable_id','ip_address','user_agent','old_values','new_values','description'];
    protected $casts = ['old_values' => 'array', 'new_values' => 'array'];
}
