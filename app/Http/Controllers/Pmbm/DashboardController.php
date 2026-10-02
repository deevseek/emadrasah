<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pmbm;

use App\Enums\Pmbm\{ApplicantStatus, PaymentStatus};
use App\Http\Controllers\Controller;
use App\Models\Pmbm\{PmbmApplicant, PmbmPayment};
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $counts=PmbmApplicant::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate','status');
        return view('pmbm.admin.dashboard',['metrics'=>['Total pendaftar'=>PmbmApplicant::count(),'Online'=>PmbmApplicant::where('registration_source','online')->count(),'Offline'=>PmbmApplicant::where('registration_source','offline')->count(),'Menunggu verifikasi'=>($counts[ApplicantStatus::Submitted->value]??0)+($counts[ApplicantStatus::DocumentVerification->value]??0),'Menunggu pembayaran awal'=>$counts[ApplicantStatus::InitialPaymentPending->value]??0,'Siap fitting'=>$counts[ApplicantStatus::InitialPaymentVerified->value]??0,'Menunggu wawancara'=>($counts[ApplicantStatus::FittingCompleted->value]??0)+($counts[ApplicantStatus::InterviewScheduled->value]??0),'Menunggu keputusan'=>$counts[ApplicantStatus::DecisionPending->value]??0,'Diterima'=>$counts[ApplicantStatus::Accepted->value]??0,'Cadangan'=>$counts[ApplicantStatus::Waitlisted->value]??0,'Belum diterima'=>$counts[ApplicantStatus::Rejected->value]??0,'Sudah menjadi siswa'=>$counts[ApplicantStatus::Enrolled->value]??0,'Pembayaran terverifikasi'=>'Rp '.number_format((float)PmbmPayment::where('status',PaymentStatus::Verified)->sum('amount'),0,',','.')]]);
    }
}
