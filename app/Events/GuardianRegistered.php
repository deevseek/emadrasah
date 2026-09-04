<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\GuardianProfile;
use App\Models\Student;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GuardianRegistered implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly GuardianProfile $guardian,
        public readonly Student $student,
    ) {}
}
