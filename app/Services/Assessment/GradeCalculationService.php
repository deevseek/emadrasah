<?php

declare(strict_types=1);

namespace App\Services\Assessment;

class GradeCalculationService
{
    public function weighted(array $components, int $precision = 2): ?float
    {
        $total = 0.0;
        $weight = 0.0;
        foreach ($components as $component) {
            if (! ($component['active'] ?? true) || ($component['score'] ?? null) === null) {
                continue;
            }
            $w = (float) ($component['weight'] ?? 0);
            $total += ((float) $component['score']) * $w / 100;
            $weight += $w;
        }
        return $weight === 0.0 ? null : round($total, $precision);
    }
}
