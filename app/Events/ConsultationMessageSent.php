<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\TeacherConsultationMessage;
use Illuminate\Foundation\Events\Dispatchable;

class ConsultationMessageSent
{
    use Dispatchable;

    public function __construct(public readonly TeacherConsultationMessage $message) {}
}
