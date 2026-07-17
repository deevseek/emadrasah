<?php

namespace App\Providers;

use App\Models\AcademicYear;
use App\Models\Semester;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, string $ability): ?bool {
            return $user->hasAnyRole(['super-admin', 'Super Admin']) ? true : null;
        });

        View::composer('components.app-layout', function ($view): void {
            $view->with('activeYearName', AcademicYear::query()->where('is_active', true)->value('name'));
            $view->with('activeSemesterName', Semester::query()->where('is_active', true)->value('name'));
        });
    }
}
