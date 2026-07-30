<?php

declare(strict_types=1);

namespace App\Http\Requests\Foundation;

class UpdateAcademicPeriodRequest extends AcademicPeriodRequest
{
    protected function permission(): string { return 'academic-periods.update'; }
}
