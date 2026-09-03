<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelAttendanceDevice extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_trusted' => 'boolean',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'trusted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function trustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trusted_by');
    }
}
