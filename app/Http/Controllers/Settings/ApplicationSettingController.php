<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateApplicationSettingRequest;
use App\Services\Settings\ApplicationSettingService;
use App\Contracts\FaceRecognitionService;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ApplicationSettingController extends Controller
{
    public function __construct(private ApplicationSettingService $settings) {}

    public function edit(FaceRecognitionService $faces): View
    {
        return view('settings.application.edit', ['settings' => $this->settings->all(), 'timezones' => DateTimeZone::listIdentifiers(), 'title' => 'Pengaturan Aplikasi', 'faceService' => ['provider' => $faces->provider(), ...$faces->health()]]);
    }

    public function update(UpdateApplicationSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $files = collect(['primary_logo', 'login_logo', 'print_logo', 'favicon'])->mapWithKeys(fn (string $key) => [$key => $request->file($key)])->filter()->all();
        $this->settings->update($request->user(), collect($validated)->except(['primary_logo', 'login_logo', 'print_logo', 'favicon'])->all(), $files);

        return back()->with('status', 'Pengaturan aplikasi berhasil diperbarui.');
    }
}
