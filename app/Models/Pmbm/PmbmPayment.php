<?php

declare(strict_types=1);

namespace App\Models\Pmbm;

use App\Enums\Pmbm\{PaymentStage, PaymentStatus};
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PmbmPayment extends Model
{
    protected $fillable = ['applicant_id', 'payment_stage', 'amount', 'payment_method', 'reference_number', 'proof_path', 'paid_at', 'status', 'verification_note', 'received_by', 'verified_by', 'verified_at'];

    protected function casts(): array
    {
        return ['payment_stage' => PaymentStage::class, 'status' => PaymentStatus::class, 'amount' => 'decimal:2', 'paid_at' => 'datetime', 'verified_at' => 'datetime'];
    }

    public function applicant(): BelongsTo { return $this->belongsTo(PmbmApplicant::class, 'applicant_id'); }
    public function receiver(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
}
