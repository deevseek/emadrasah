<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FeeType extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_mandatory' => 'boolean',
            'default_amount' => 'decimal:2',
        ];
    }

    public function revenueAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'revenue_account_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(StudentInvoice::class);
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(StudentDiscount::class);
    }
}
