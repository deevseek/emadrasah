<?php

declare(strict_types=1);
namespace App\Events;
use App\Models\Finance\StudentPayment;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
class StudentPaymentCompleted implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    public function __construct(public readonly StudentPayment $payment) {}
}
