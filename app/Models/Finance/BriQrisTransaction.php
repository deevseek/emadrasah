<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

final class BriQrisTransaction extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['amount' => 'decimal:2', 'expires_at' => 'datetime', 'last_inquired_at' => 'datetime']; }
    public function invoice() { return $this->belongsTo(StudentInvoice::class, 'invoice_id'); }
}
