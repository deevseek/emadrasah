<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OutgoingEmailStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutgoingEmail extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'to_addresses' => 'array',
            'cc_addresses' => 'array',
            'bcc_addresses' => 'array',
            'attachments' => 'array',
            'status' => OutgoingEmailStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
