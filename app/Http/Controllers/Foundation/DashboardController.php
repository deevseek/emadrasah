<?php

declare(strict_types=1);

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\User;
use App\Services\Foundation\SchoolProfileService;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function __invoke(SchoolProfileService $profiles): View
    {
        $profile = $profiles->current();
        $activeYear = AcademicYear::query()->where('is_active', true)->first();
        $activeSemester = Semester::query()->with('academicYear')->where('is_active', true)->first();
        $inactiveUsers = User::query()->where('is_active', false)->count();

        return view('dashboard', [
            'title' => 'Beranda Fondasi Madrasah',
            'profile' => $profile,
            'activeYear' => $activeYear,
            'activeSemester' => $activeSemester,
            'activeUsers' => User::query()->where('is_active', true)->count(),
            'inactiveUsers' => $inactiveUsers,
            'profileComplete' => $profiles->isComplete($profile),
            'latestActivities' => Activity::query()->where('log_name', 'foundation')->latest()->limit(5)->get(),
        ]);
    }
}
