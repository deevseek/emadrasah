<?php

declare(strict_types=1);

namespace App\Models;

class Role extends \Spatie\Permission\Models\Role
{
    protected $fillable = [
        'name',
        'guard_name',
        'display_name',
    ];
}
