<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginHistory extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'email', 'ip_address', 'user_agent', 'successful', 'failure_reason', 'attempted_at'];

    protected $casts = ['successful' => 'boolean', 'attempted_at' => 'datetime'];
}
