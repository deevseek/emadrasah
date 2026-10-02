<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pmbm;

use App\Enums\Pmbm\{ApplicantStatus, PaymentStatus};
use App\Http\Controllers\Controller;
use App\Models\Pmbm\{PmbmApplicant, PmbmPayment};
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Models\Pmbm\PmbmSetting;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $settings=PmbmSetting::with('academicYear')->latest()->get();$setting=$settings->firstWhere('id',$request->integer('setting_id'))??$settings->firstWhere('enabled',true)??$settings->first();$applicants=PmbmApplicant::query()->when($setting,fn($q)=>$q->where('academic_year_id',$setting->academic_year_id));$counts=(clone $applicants)->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate','status');$payment=PmbmPayment::whereHas('applicant',fn($q)=>$setting?$q->where('academic_year_id',$setting->academic_year_id):$q);
        return view('pmbm.admin.dashboard',['settings'=>$settings,'setting'=>$setting,'metrics'=>['Total pendaftar'=>(clone $applicants)->count(),'Online'=>(clone $applicants)->where('registration_source','online')->count(),'Offline'=>(clone $applicants)->where('registration_source','offline')->count(),'Menunggu verifikasi'=>($counts[ApplicantStatus::Submitted->value]??0)+($counts[ApplicantStatus::DocumentVerification->value]??0),'Menunggu pembayaran awal'=>$counts[ApplicantStatus::InitialPaymentPending->value]??0,'Siap fitting'=>$counts[ApplicantStatus::InitialPaymentVerified->value]??0,'Menunggu keputusan'=>$counts[ApplicantStatus::DecisionPending->value]??0,'Diterima'=>$counts[ApplicantStatus::Accepted->value]??0,'Cadangan'=>$counts[ApplicantStatus::Waitlisted->value]??0,'Belum diterima'=>$counts[ApplicantStatus::Rejected->value]??0,'Sudah menjadi siswa'=>$counts[ApplicantStatus::Enrolled->value]??0,'Pembayaran terverifikasi'=>'Rp '.number_format((float)$payment->where('status',PaymentStatus::Verified)->sum('amount'),0,',','.')]]);
    }
}
