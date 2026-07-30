<?php

declare(strict_types=1);

namespace App\Http\Requests\Foundation;

class StoreAcademicPeriodRequest extends AcademicPeriodRequest
{
    protected function permission(): string { return 'academic-periods.create'; }
}
