<?php

declare(strict_types=1);

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Services\Foundation\AcademicPeriodService;
use Illuminate\Http\RedirectResponse;

class AcademicPeriodActivationController extends Controller
{
    public function update(Semester $semester, AcademicPeriodService $service): RedirectResponse
    {
        $changed = $service->activate($semester, request()->user());
        return back()->with('status', $changed ? 'Periode akademik berhasil diaktifkan.' : 'Periode akademik tersebut sudah aktif.');
    }
}
