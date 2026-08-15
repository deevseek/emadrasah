<?php

namespace App\Providers;

use App\Services\Foundation\AcademicPeriodService;
use App\Services\Foundation\SchoolProfileService;
use App\Services\Settings\ApplicationSettingService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Contracts\FaceRecognitionService;
use App\Services\Hrd\UnavailableFaceRecognitionService;
use App\Services\Hrd\PythonFaceRecognitionService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SchoolProfileService::class);
        $this->app->singleton(AcademicPeriodService::class);
        $this->app->singleton(ApplicationSettingService::class);
        $this->app->bind(FaceRecognitionService::class, config('face-recognition.driver') === 'python' ? PythonFaceRecognitionService::class : UnavailableFaceRecognitionService::class);
    }

    public function boot(): void
    {
        $settings = app(ApplicationSettingService::class);
        date_default_timezone_set((string) $settings->get('timezone', config('app.timezone')));
        config(['app.timezone' => $settings->get('timezone', config('app.timezone'))]);
        Gate::before(fn ($user, string $ability): ?bool => $user->hasRole('super-admin') ? true : null);
        View::composer('*', function ($view): void {
            $view->with('schoolProfile', app(SchoolProfileService::class)->current());
            $view->with('activeAcademicPeriod', app(AcademicPeriodService::class)->current());
            $view->with('applicationSettings', app(ApplicationSettingService::class));
        });
    }
}
