<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type', 'is_public'];
    protected $casts = ['is_public' => 'boolean'];
}
