<?php

declare(strict_types=1);

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\LoginHistory;
use App\Models\SchoolProfile;
use App\Models\SchoolSetting;
use App\Models\Semester;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'profile' => SchoolProfile::first(),
            'activeYear' => AcademicYear::where('is_active', true)->first(),
            'activeSemester' => Semester::where('is_active', true)->first(),
            'userCount' => User::count(),
            'activeUserCount' => User::where('is_active', true)->count(),
            'loginCountToday' => LoginHistory::whereDate('attempted_at', today())->where('successful', true)->count(),
            'settingCount' => SchoolSetting::count(),
        ]);
    }
}
