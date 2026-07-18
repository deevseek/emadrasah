<?php

declare(strict_types=1);

namespace App\Enums;

enum AssessmentCalculationMethod: string
{
    case WeightedAverage = 'weighted_average';
    case SimpleAverage = 'simple_average';
    case ManualFinalGrade = 'manual_final_grade';
}
