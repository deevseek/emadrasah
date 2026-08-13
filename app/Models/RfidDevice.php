<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfidDevice extends Model
{
    protected $guarded = [];
    protected $hidden = ['token_hash'];
    protected function casts(): array { return ['is_active' => 'boolean', 'last_seen_at' => 'datetime']; }
}
