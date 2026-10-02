<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pmbm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pmbm\UpdateSettingRequest;
use App\Models\AcademicYear;
use App\Models\Pmbm\PmbmSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View { return view('pmbm.admin.settings', ['settings'=>PmbmSetting::with(['academicYear','documentRequirements'])->latest()->get(),'years'=>AcademicYear::whereDoesntHave('pmbmSetting')->latest('starts_at')->get()]); }
    public function create(): View { return view('pmbm.admin.settings-form', ['setting'=>new PmbmSetting(['minimum_initial_payment_percent'=>50,'require_child_interview'=>true,'payment_proof_required'=>true,'allowed_payment_methods'=>['bank_transfer']]),'years'=>AcademicYear::whereDoesntHave('pmbmSetting')->latest('starts_at')->get()]); }
    public function edit(PmbmSetting $setting): View { return view('pmbm.admin.settings-form', ['setting'=>$setting->load('documentRequirements'),'years'=>AcademicYear::whereKey($setting->academic_year_id)->get()]); }
    public function store(UpdateSettingRequest $request): RedirectResponse
    {
        $setting=$this->persist($request);
        return redirect()->route('pmbm.settings.edit',$setting)->with('status','Pengaturan penerimaan berhasil dibuat dalam keadaan nonaktif.');
    }
    public function update(UpdateSettingRequest $request, PmbmSetting $setting): RedirectResponse
    {
        $this->persist($request,$setting);
        activity('pmbm')->causedBy($request->user())->performedOn($setting)->log('Pengaturan PMBM diperbarui.');
        return back()->with('status','Pengaturan PMBM berhasil diperbarui.');
    }
    private function persist(UpdateSettingRequest $request,?PmbmSetting $setting=null):PmbmSetting{return DB::transaction(function()use($request,$setting){$data=$request->validated();$requirements=$data['document_requirements']??[];unset($data['document_requirements']);foreach(['enabled','online_registration_enabled','offline_registration_enabled','payment_proof_required','require_fitting','require_parent_interview','require_child_interview','require_child_observation','require_headmaster_approval','require_payment_before_registration_complete'] as $field)$data[$field]=$request->boolean($field);if(!$setting){$data['enabled']=false;$setting=PmbmSetting::create($data);}else{$setting->update($data);}if($setting->enabled)PmbmSetting::where('id','!=',$setting->id)->where('enabled',true)->update(['enabled'=>false]);$setting->documentRequirements()->delete();foreach($requirements as $i=>$requirement)$setting->documentRequirements()->create($requirement+['is_required'=>(bool)($requirement['is_required']??false),'is_active'=>(bool)($requirement['is_active']??false),'sort_order'=>$i]);return $setting;});}
}
