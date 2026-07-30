<?php

declare(strict_types=1);

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Services\Foundation\AcademicPeriodService;
use App\Services\Foundation\SchoolProfileService;
use Illuminate\View\View;
use App\Models\{Personnel, Student};

class DashboardController extends Controller
{
    public function __invoke(SchoolProfileService $service, AcademicPeriodService $periodService): View
    {
        return view('dashboard', ['title' => 'Dashboard', 'profile' => $service->current(), 'academicPeriod' => $periodService->current(), 'personnelStats' => ['total'=>Personnel::count(),'active'=>Personnel::where('is_active',true)->count(),'without_account'=>Personnel::whereNull('user_id')->count()],'studentStats'=>['total'=>Student::count(),'active'=>Student::where('status','active')->count(),'incomplete_parents'=>Student::where(fn($q)=>$q->whereNull('father_name')->orWhereNull('mother_name'))->count()]]);
    }
}
