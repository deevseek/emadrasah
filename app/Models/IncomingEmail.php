<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomingEmail extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'to_addresses' => 'array',
            'cc_addresses' => 'array',
            'attachments' => 'array',
            'received_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'read_by')->withTrashed();
    }
}
