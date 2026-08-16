<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Hrd\GetFaceRecognitionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateApplicationSettingRequest;
use App\Services\Settings\ApplicationSettingService;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationSettingController extends Controller
{
    public function __construct(private ApplicationSettingService $settings) {}

    public function edit(Request $request, GetFaceRecognitionStatus $faceStatus): View
    {
        return view('settings.application.edit', [
            'settings' => $this->settings->all(),
            'timezones' => DateTimeZone::listIdentifiers(),
            'title' => 'Pengaturan Aplikasi',
            'faceStatus' => $request->user()->can('hrd-settings.view') ? $faceStatus->handle() : null,
        ]);
    }

    public function faceRecognitionStatus(GetFaceRecognitionStatus $faceStatus): JsonResponse
    {
        return response()->json($faceStatus->handle());
    }

    public function update(UpdateApplicationSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $files = collect(['primary_logo', 'login_logo', 'print_logo', 'favicon'])->mapWithKeys(fn (string $key) => [$key => $request->file($key)])->filter()->all();
        $this->settings->update($request->user(), collect($validated)->except(['primary_logo', 'login_logo', 'print_logo', 'favicon'])->all(), $files);

        return back()->with('status', 'Pengaturan aplikasi berhasil diperbarui.');
    }
}
