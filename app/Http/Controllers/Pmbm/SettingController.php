<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pmbm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pmbm\UpdateSettingRequest;
use App\Models\AcademicYear;
use App\Models\Pmbm\PmbmSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View { return view('pmbm.admin.settings', ['settings'=>PmbmSetting::with('academicYear')->latest()->get(),'years'=>AcademicYear::latest('starts_at')->get()]); }
    public function update(UpdateSettingRequest $request, PmbmSetting $setting): RedirectResponse
    {
        $data=$request->validated();
        foreach(['enabled','online_registration_enabled','offline_registration_enabled','require_fitting','require_parent_interview','require_child_observation','require_headmaster_approval','require_payment_before_registration_complete'] as $field) $data[$field]=$request->boolean($field);
        $setting->update($data);
        activity('pmbm')->causedBy($request->user())->performedOn($setting)->log('Pengaturan PMBM diperbarui.');
        return back()->with('status','Pengaturan PMBM berhasil diperbarui.');
    }
}
