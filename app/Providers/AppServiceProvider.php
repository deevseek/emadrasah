<?php

namespace App\Providers;

use App\Services\Foundation\AcademicPeriodService;
use App\Services\Foundation\SchoolProfileService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SchoolProfileService::class);
        $this->app->singleton(AcademicPeriodService::class);
    }

    public function boot(): void
    {
        Gate::before(fn ($user, string $ability): ?bool => $user->hasRole('super-admin') ? true : null);
        View::composer('*', function ($view): void {
            $view->with('schoolProfile', app(SchoolProfileService::class)->current());
            $view->with('activeAcademicPeriod', app(AcademicPeriodService::class)->current());
        });
    }
}
