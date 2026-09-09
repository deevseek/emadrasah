<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrd\HrdReportFilterRequest;
use App\Services\Foundation\SchoolProfileService;
use App\Services\Hrd\HrdReportService;
use Illuminate\View\View;

class HrdReportController extends Controller
{
    public function index(HrdReportFilterRequest $request, HrdReportService $reports): View
    {
        [$start, $end] = $request->period();

        return view('hrd.reports.index', ['title' => 'Laporan HRD', ...$reports->data($start, $end)]);
    }

    public function print(HrdReportFilterRequest $request, HrdReportService $reports, SchoolProfileService $profiles): View
    {
        [$start, $end] = $request->period();

        return view('hrd.reports.print', [
            'title' => 'Laporan HRD / Kepegawaian',
            'school' => $profiles->current(),
            'officer' => $request->user()->loadMissing('personnel')->personnel,
            ...$reports->data($start, $end, false),
        ]);
    }
}
