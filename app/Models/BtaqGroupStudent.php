<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BtaqGroupStudent extends Model
{
    protected $fillable = [
        'btaq_group_id',
        'student_id',
        'joined_at',
        'completed_at',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'date',
            'completed_at' => 'date',
        ];
    }
}
