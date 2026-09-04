<?php

namespace App\Providers;

use App\Services\Foundation\AcademicPeriodService;
use App\Services\Foundation\SchoolProfileService;
use App\Services\Settings\ApplicationSettingService;
use Illuminate\Support\Facades\{Event,Gate};
use App\Events\StudentPaymentCompleted;
use App\Listeners\SendSppPaymentReceiptEmail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Contracts\FaceRecognitionService;
use App\Contracts\Banking\{BankPaymentGateway, BankTransferGateway};
use App\Services\Banking\{DisabledBriGateway, FakeBriGateway};
use App\Services\Hrd\UnavailableFaceRecognitionService;
use App\Observers\ActivityObserver;
use App\Services\Hrd\PythonFaceRecognitionService;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SchoolProfileService::class);
        $this->app->singleton(AcademicPeriodService::class);
        $this->app->singleton(ApplicationSettingService::class);
        $bankGateway = app()->environment('testing') ? FakeBriGateway::class : DisabledBriGateway::class;
        $this->app->bind(BankPaymentGateway::class, $bankGateway);
        $this->app->bind(BankTransferGateway::class, $bankGateway);
        $this->app->bind(FaceRecognitionService::class, config('face-recognition.driver') === 'python' ? PythonFaceRecognitionService::class : UnavailableFaceRecognitionService::class);
    }

    public function boot(): void
    {
        Activity::observe(ActivityObserver::class);
        Event::listen(StudentPaymentCompleted::class, SendSppPaymentReceiptEmail::class);
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
