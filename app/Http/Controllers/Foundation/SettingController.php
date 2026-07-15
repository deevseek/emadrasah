<?php

declare(strict_types=1);

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Models\SchoolSetting;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        return view('foundation.settings.index', [
            'settings' => SchoolSetting::orderBy('group')->orderBy('key')->paginate(30),
        ]);
    }

    public function update(Request $request, SchoolSetting $setting, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'value' => ['nullable', 'string', 'max:2000'],
        ]);
        $old = $setting->toArray();

        $setting->update($data);
        $logger->log('setting.updated', $setting, $old, $setting->fresh()->toArray());

        return back()->with('status', 'Pengaturan diperbarui.');
    }
}
