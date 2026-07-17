<?php

declare(strict_types=1);

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Services\Foundation\DashboardMetricsService;
use App\Services\Foundation\SchoolProfileService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardMetricsService $metrics, SchoolProfileService $profiles): View
    {
        return view('dashboard', [
            'title' => 'Beranda e-Madrasah',
            'moduleContext' => 'Ringkasan operasional e-Madrasah berdasarkan data madrasah terkini.',
            ...$metrics->summary($profiles),
        ]);
    }
}
