<?php

declare(strict_types=1);

namespace App\Models\Pmbm;

use Illuminate\Database\Eloquent\Model;

class PmbmChildInterview extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    }
}
