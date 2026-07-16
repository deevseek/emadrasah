<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BtaqLevel extends Model
{
    protected $fillable = [
        'code',
        'name',
        'sequence',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
