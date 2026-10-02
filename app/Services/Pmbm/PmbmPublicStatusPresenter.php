<?php
declare(strict_types=1);
namespace App\Services\Pmbm;
use App\Enums\Pmbm\ApplicantStatus;
use App\Models\Pmbm\PmbmApplicant;
use Illuminate\Support\Facades\URL;
class PmbmPublicStatusPresenter {
 public function present(PmbmApplicant $applicant,array $payment):array {
  $decision=$applicant->decision;
  if($decision?->finalized_at&&!$decision->published_at)return ['title'=>'Menunggu Pengumuman','description'=>'Keputusan internal telah selesai dan akan tampil setelah dipublikasikan.','can_pay_initial'=>false,'acceptance_url'=>null];
  $copy=match($applicant->status){
   ApplicantStatus::Submitted,ApplicantStatus::DocumentVerification=>['Pendaftaran diterima','Verifikasi dokumen dan pembayaran awal berjalan secara paralel.'],
   ApplicantStatus::InitialPaymentPending=>['Dokumen lengkap','Selesaikan pembayaran awal sesuai minimum penerimaan.'],
   ApplicantStatus::InitialPaymentVerified=>['Prasyarat terpenuhi','Tunggu penjadwalan tahap penerimaan berikutnya.'],
   ApplicantStatus::FittingScheduled=>['Fitting dijadwalkan','Hadiri fitting sesuai jadwal yang ditentukan panitia.'],
   ApplicantStatus::FittingCompleted=>['Fitting selesai','Tunggu penjadwalan wawancara berikutnya.'],
   ApplicantStatus::InterviewScheduled=>['Wawancara orang tua dijadwalkan','Hadiri wawancara sesuai jadwal.'],
   ApplicantStatus::ChildInterviewScheduled=>['Wawancara anak dijadwalkan','Hadiri wawancara anak sesuai jadwal.'],
   ApplicantStatus::ObservationScheduled=>['Observasi dijadwalkan','Hadiri observasi sesuai jadwal.'],
   ApplicantStatus::DecisionPending=>['Menunggu keputusan','Panitia sedang memproses rekomendasi dan pengesahan hasil.'],
   ApplicantStatus::Accepted,ApplicantStatus::RegistrationPaymentPending,ApplicantStatus::RegistrationPaymentPartial=>['Diterima','Silakan menyelesaikan proses daftar ulang.'],
   ApplicantStatus::Waitlisted=>['Cadangan','Nomor urut cadangan: '.($decision?->waitlist_order??'—').'.'],
   ApplicantStatus::Rejected=>['Belum diterima','Terima kasih telah mengikuti proses penerimaan.'],
   ApplicantStatus::Registered=>['Daftar ulang selesai','Administrasi daftar ulang telah diselesaikan.'],ApplicantStatus::Enrolled=>['Telah menjadi siswa','Data calon murid telah dikonversi menjadi data siswa.'],default=>[$applicant->status->label(),'Ikuti informasi resmi dari panitia penerimaan.']};
  $accepted=$decision?->published_at&&$decision->decision==='accepted';
  return ['title'=>$copy[0],'description'=>$copy[1],'can_pay_initial'=>!$payment['initial_satisfied']&&in_array($applicant->status,[ApplicantStatus::Submitted,ApplicantStatus::DocumentVerification,ApplicantStatus::Verified,ApplicantStatus::InitialPaymentPending,ApplicantStatus::InitialPaymentVerified],true),'acceptance_url'=>$accepted?URL::temporarySignedRoute('pmbm.public.acceptance',now()->addHours(24),['token'=>$applicant->public_token]):null];
 }
}
