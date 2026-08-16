<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Hrd\GetFaceRecognitionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateGeneralSettingRequest;
use App\Http\Requests\Settings\UpdateBriSettingRequest;
use App\Http\Requests\Settings\UpdateHrdSettingRequest;
use App\Models\BriIntegrationSetting;
use App\Services\Finance\BriConfigurationService;
use App\Services\Finance\BriConnectionService;
use App\Services\Finance\BriSettingService;
use App\Services\Settings\ApplicationSettingService;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationSettingController extends Controller
{
    public function __construct(private ApplicationSettingService $settings) {}

    public function edit(Request $request, GetFaceRecognitionStatus $faceStatus, BriConfigurationService $bri): View
    {
        return view('settings.application.edit', [
            'settings' => $this->settings->all(),
            'timezones' => DateTimeZone::listIdentifiers(),
            'title' => 'Pengaturan Aplikasi',
            'faceStatus' => $request->user()->can('hrd-settings.view') ? $faceStatus->handle() : null,
            'bri' => $bri,
            'briSetting' => $bri->setting(),
        ]);
    }

    public function faceRecognitionStatus(GetFaceRecognitionStatus $faceStatus): JsonResponse
    {
        return response()->json($faceStatus->handle());
    }

    public function update(UpdateGeneralSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $files = collect(['primary_logo', 'login_logo', 'print_logo', 'favicon'])->mapWithKeys(fn (string $key) => [$key => $request->file($key)])->filter()->all();
        $this->settings->update($request->user(), collect($validated)->except(['primary_logo', 'login_logo', 'print_logo', 'favicon'])->all(), $files);

        return back()->with('status', 'Pengaturan aplikasi berhasil diperbarui.');
    }

    public function updateHrd(UpdateHrdSettingRequest $request): RedirectResponse
    {
        $this->settings->update($request->user(), $request->validated());
        return back()->with('status', 'Pengaturan HRD berhasil diperbarui.');
    }

    public function updateBri(UpdateBriSettingRequest $request, BriSettingService $service): RedirectResponse
    {
        $service->update($request->user(), $request->safe()->except(['private_key','public_key']), $request->file('private_key'), $request->file('public_key'));
        return back()->with('status', 'Konfigurasi BRI berhasil diperbarui.');
    }

    public function testBri(Request $request, BriConnectionService $connection): RedirectResponse
    {
        abort_unless($request->user()->can('finance.bri.configure'), 403);
        $result=$connection->test();
        if($setting=BriIntegrationSetting::query()->first())$setting->update(['last_connection_at'=>now(),'last_connection_success'=>$result['success'],'last_connection_message'=>$result['message']]);
        return back()->with($result['success']?'status':'error',$result['message']);
    }
}
