<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RfidCommandStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfidDeviceCommand extends Model
{
    protected $guarded = [];
    protected $hidden = ['payload'];
    protected function casts(): array { return ['payload' => 'array', 'result' => 'array', 'status' => RfidCommandStatus::class, 'replaces_existing' => 'boolean', 'expires_at' => 'datetime', 'started_at' => 'datetime', 'completed_at' => 'datetime', 'failed_at' => 'datetime']; }
    public function device(): BelongsTo { return $this->belongsTo(RfidDevice::class, 'device_id'); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
}
