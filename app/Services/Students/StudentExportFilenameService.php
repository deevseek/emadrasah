<?php

declare(strict_types=1);

namespace App\Services\Students;

class StudentExportFilenameService
{
    public function make(string $prefix, ?string $classroomLabel): string
    {
        $classroom = str($classroomLabel)->trim()->slug('-')->value();
        $classroomSuffix = $classroom !== '' ? '-'.$classroom : '';

        return $prefix.$classroomSuffix.'-'.now()->format('Y-m-d-Hi').'.xlsx';
    }
}
