<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RfidDeviceType;
use Illuminate\Database\Eloquent\Model;

class RfidDevice extends Model
{
    protected $guarded = [];
    protected $hidden = ['token_hash'];
    protected function casts(): array { return ['is_active' => 'boolean', 'last_seen_at' => 'datetime', 'device_type' => RfidDeviceType::class]; }
    public function isOnline(): bool { return $this->is_active && $this->last_seen_at?->gte(now()->subSeconds(75)); }
}
