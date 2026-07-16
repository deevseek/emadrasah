<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PredicateRange extends Model
{
    protected $fillable = [
        'code',
        'label',
        'minimum_score',
        'maximum_score',
        'description_template',
        'sequence',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'minimum_score' => 'decimal:2',
            'maximum_score' => 'decimal:2',
            'sequence' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
