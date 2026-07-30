<?php

declare(strict_types=1);

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Services\Foundation\SchoolProfileService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(SchoolProfileService $service): View
    {
        return view('dashboard', ['title' => 'Dashboard', 'profile' => $service->current()]);
    }
}
