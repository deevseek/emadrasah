<?php

declare(strict_types=1);
namespace App\Services\Pmbm;
use App\Enums\Pmbm\ApplicantStatus;
use App\Mail\PmbmRegistrationReceiptMail;
use App\Models\{AcademicYear,Student,User};
use App\Models\Pmbm\{PmbmApplicant,PmbmApplicantDocument,PmbmDocumentRequirement,PmbmSetting,PmbmStatusHistory};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB,Mail};
use Illuminate\Validation\ValidationException;
class PmbmRegistrationService {
 public function submit(array $data,string $source,?User $actor=null): PmbmApplicant { return DB::transaction(function () use ($data,$source,$actor) {
  $setting=PmbmSetting::where('academic_year_id',$data['academic_year_id'])->lockForUpdate()->first();
  $this->assertAvailable($setting,$source); $year=AcademicYear::findOrFail($data['academic_year_id']);
  if ($data['nik'] ?? null) { if (PmbmApplicant::where('academic_year_id',$year->id)->where('nik',$data['nik'])->exists()) throw ValidationException::withMessages(['nik'=>'NIK sudah digunakan pada pendaftaran tahun ajaran ini.']); }
  $documents=$data['documents']??[]; $this->assertRequiredDocuments($documents); unset($data['documents'],$data['consent']);
  $number=$setting->registration_prefix.'-'.$year->starts_at->format('Y').'-'.str_pad((string)(PmbmApplicant::where('academic_year_id',$year->id)->lockForUpdate()->count()+1),4,'0',STR_PAD_LEFT);
  $applicant=PmbmApplicant::create(['academic_year_id'=>$year->id,'registration_number'=>$number,'registration_source'=>$source,'status'=>ApplicantStatus::Draft,'full_name'=>$data['full_name'],'nickname'=>$data['nickname']??null,'nik'=>$data['nik']??null,'nisn'=>$data['nisn']??null,'citizenship'=>$data['citizenship'],'gender'=>$data['gender'],'religion'=>$data['religion'],'aspiration'=>$data['aspiration']??null,'child_order'=>$data['child_order']??null,'sibling_count'=>$data['sibling_count']??null,'birth_place'=>$data['birth_place'],'birth_date'=>$data['birth_date'],'parent_email'=>$data['parent_email']??null,'parent_phone'=>$data['parent_phone'],'data'=>$data,'submitted_at'=>now(),'consent_at'=>now(),'public_token'=>bin2hex(random_bytes(32)),'created_by'=>$actor?->id]);
  foreach($documents as $type=>$file) if($file instanceof UploadedFile) $this->storeDocument($applicant,$type,$file);
  $this->transition($applicant,ApplicantStatus::Submitted,$actor,'Pendaftaran dikirim.');
  if ($applicant->parent_email) DB::afterCommit(fn()=>Mail::to($applicant->parent_email)->queue(new PmbmRegistrationReceiptMail($applicant)));
  return $applicant;
 }); }
 private function assertAvailable(?PmbmSetting $setting,string $source): void { if(!$setting||!$setting->enabled||($source==='online'&&!$setting->online_registration_enabled)||($source==='offline'&&!$setting->offline_registration_enabled)||($source==='online'&&$setting->registration_open_at&&now()->lt($setting->registration_open_at))||($source==='online'&&$setting->registration_close_at&&now()->gt($setting->registration_close_at))||($setting->quota_total && PmbmApplicant::where('academic_year_id',$setting->academic_year_id)->count() >= $setting->quota_total)) throw ValidationException::withMessages(['academic_year_id'=>'Pendaftaran PMBM tidak sedang dibuka untuk tahun ajaran ini.']); }
 private function assertRequiredDocuments(array $documents): void { /* Berkas boleh menyusul dan diverifikasi paralel dengan pembayaran. */ }
 public function update(PmbmApplicant $applicant,array $data,?User $actor=null): void { DB::transaction(function()use($applicant,$data,$actor){$documents=$data['documents']??[];unset($data['documents'],$data['consent']);$applicant->update(['academic_year_id'=>$data['academic_year_id'],'full_name'=>$data['full_name'],'nickname'=>$data['nickname']??null,'nik'=>$data['nik']??null,'nisn'=>$data['nisn']??null,'citizenship'=>$data['citizenship'],'gender'=>$data['gender'],'religion'=>$data['religion'],'aspiration'=>$data['aspiration']??null,'child_order'=>$data['child_order']??null,'sibling_count'=>$data['sibling_count']??null,'birth_place'=>$data['birth_place'],'birth_date'=>$data['birth_date'],'parent_email'=>$data['parent_email']??null,'parent_phone'=>$data['parent_phone'],'data'=>$data]);foreach($documents as $type=>$file)if($file instanceof UploadedFile)$this->storeDocument($applicant,$type,$file);}); }
 public function replaceDocument(PmbmApplicant $applicant,string $type,UploadedFile $file):PmbmApplicantDocument{return DB::transaction(function()use($applicant,$type,$file){$old=$applicant->currentDocuments()->where('document_type',$type)->lockForUpdate()->latest('version')->first();if($old)$old->update(['is_current'=>false]);return PmbmApplicantDocument::create(['applicant_id'=>$applicant->id,'document_type'=>$type,'original_name'=>$file->getClientOriginalName(),'path'=>$file->store('pmbm/'.$applicant->id,'local'),'status'=>'uploaded','version'=>($old?->version??0)+1,'is_current'=>true,'replaces_document_id'=>$old?->id]);});}
 private function storeDocument(PmbmApplicant $applicant,string $type,UploadedFile $file):PmbmApplicantDocument{return $this->replaceDocument($applicant,$type,$file);}
 public function transition(PmbmApplicant $applicant, ApplicantStatus $status, ?User $actor, string $note = ''): void
 {
  $current = $applicant->status;
  if ($current === $status) return;
  $prerequisites=[ApplicantStatus::Submitted,ApplicantStatus::DocumentVerification,ApplicantStatus::Verified,ApplicantStatus::InitialPaymentPending,ApplicantStatus::InitialPaymentVerified];
  if(in_array($current,$prerequisites,true)&&in_array($status,$prerequisites,true)){$allowedTransition=true;}else{$allowedTransition=false;}
  $allowed = [
   ApplicantStatus::Draft->value => [ApplicantStatus::Submitted],
   ApplicantStatus::Submitted->value => [ApplicantStatus::DocumentVerification],
   ApplicantStatus::DocumentVerification->value => [ApplicantStatus::Verified, ApplicantStatus::InitialPaymentPending],
   ApplicantStatus::Verified->value => [ApplicantStatus::InitialPaymentPending, ApplicantStatus::FittingScheduled, ApplicantStatus::InterviewScheduled, ApplicantStatus::DecisionPending],
   ApplicantStatus::InitialPaymentPending->value => [ApplicantStatus::InitialPaymentVerified],
   ApplicantStatus::InitialPaymentVerified->value => [ApplicantStatus::FittingScheduled, ApplicantStatus::InterviewScheduled, ApplicantStatus::ChildInterviewScheduled, ApplicantStatus::ObservationScheduled, ApplicantStatus::DecisionPending],
   ApplicantStatus::FittingScheduled->value => [ApplicantStatus::FittingCompleted],
   ApplicantStatus::FittingCompleted->value => [ApplicantStatus::InterviewScheduled, ApplicantStatus::ChildInterviewScheduled, ApplicantStatus::ObservationScheduled, ApplicantStatus::DecisionPending],
   ApplicantStatus::InterviewScheduled->value => [ApplicantStatus::Interviewed],
   ApplicantStatus::Interviewed->value => [ApplicantStatus::ChildInterviewScheduled, ApplicantStatus::ObservationScheduled, ApplicantStatus::DecisionPending],
   ApplicantStatus::ChildInterviewScheduled->value => [ApplicantStatus::ChildInterviewed],
   ApplicantStatus::ChildInterviewed->value => [ApplicantStatus::ObservationScheduled, ApplicantStatus::DecisionPending],
   ApplicantStatus::ObservationScheduled->value => [ApplicantStatus::Observed],
   ApplicantStatus::Observed->value => [ApplicantStatus::DecisionPending],
   ApplicantStatus::DecisionPending->value => [ApplicantStatus::Accepted, ApplicantStatus::Waitlisted, ApplicantStatus::Rejected],
   ApplicantStatus::Accepted->value => [ApplicantStatus::RegistrationPaymentPending, ApplicantStatus::Registered],
   ApplicantStatus::RegistrationPaymentPending->value => [ApplicantStatus::RegistrationPaymentPartial, ApplicantStatus::Registered],
   ApplicantStatus::RegistrationPaymentPartial->value => [ApplicantStatus::Registered],
   ApplicantStatus::Registered->value => [ApplicantStatus::Enrolled],
  ];
  if (! $allowedTransition && ! in_array($status, $allowed[$current?->value] ?? [], true)) throw ValidationException::withMessages(['status' => 'Perubahan status PMBM tidak sesuai tahapan proses.']);
  $applicant->update(['status'=>$status]);
  PmbmStatusHistory::create(['applicant_id'=>$applicant->id,'from_status'=>$current?->value,'to_status'=>$status->value,'changed_by'=>$actor?->id,'note'=>$note,'changed_at'=>now()]);
  if (function_exists('activity')) activity('pmbm')->causedBy($actor)->performedOn($applicant)->withProperties(['from'=>$current?->value,'to'=>$status->value])->log('Status PMBM diperbarui.');
 }
 public function startDocumentVerification(PmbmApplicant $applicant, ?User $actor): void
 {
  $this->transition($applicant, ApplicantStatus::DocumentVerification, $actor, 'Verifikasi berkas dimulai.');
 }
 public function verifyDocument(PmbmApplicant $applicant, PmbmApplicantDocument $document, string $status, ?string $note, User $actor): void
 {
  if ($document->applicant_id !== $applicant->id) abort(404);
  if ($applicant->status === ApplicantStatus::Submitted) $this->startDocumentVerification($applicant, $actor);
  if (!in_array($applicant->status,[ApplicantStatus::DocumentVerification,ApplicantStatus::InitialPaymentPending,ApplicantStatus::InitialPaymentVerified],true)) throw ValidationException::withMessages(['status'=>'Dokumen hanya dapat diverifikasi pada tahap prasyarat.']);
  if(!$document->is_current)throw ValidationException::withMessages(['document'=>'Versi lama dokumen tidak dapat diverifikasi.']);
  $document->update(['status'=>$status,'verification_note'=>$note,'verified_by'=>$actor->id,'verified_at'=>now()]);
  activity('pmbm')->causedBy($actor)->performedOn($applicant)->withProperties(['document_type'=>$document->document_type,'document_status'=>$status])->log('Status dokumen PMBM diperbarui.');
 }
 public function finalizeVerification(PmbmApplicant $applicant, User $actor, ?string $note = null): void
 {
  $required=PmbmDocumentRequirement::where('is_active',true)->where('is_required',true)->whereIn('channel',['both',$applicant->registration_source])->pluck('code');
  $valid=$applicant->currentDocuments()->where('status','valid')->pluck('document_type');
  $missing=$required->diff($valid);
  if ($missing->isNotEmpty()) throw ValidationException::withMessages(['documents'=>'Semua dokumen wajib harus berstatus valid sebelum berkas dinyatakan terverifikasi.']);
  app(PmbmReadinessService::class)->reconcile($applicant,$actor);
  app(PmbmWorkflowService::class)->advanceWorkflow($applicant,$actor);
 }
 public function markDecisionPending(PmbmApplicant $applicant, User $actor): void
 {
  $setting=PmbmSetting::where('academic_year_id',$applicant->academic_year_id)->first();
  if (($setting?->require_fitting && !in_array($applicant->status,[ApplicantStatus::FittingCompleted,ApplicantStatus::Interviewed],true)) || ($setting?->require_parent_interview && $applicant->status!==ApplicantStatus::Interviewed) || ($setting?->require_child_observation && !$applicant->observation?->completed_at)) throw ValidationException::withMessages(['status'=>'Tahap wajib PMBM belum diselesaikan.']);
  $this->transition($applicant, ApplicantStatus::DecisionPending, $actor, 'Tahap penetapan hasil dimulai.');
 }
 public function enroll(PmbmApplicant $applicant,User $actor):Student{return DB::transaction(function()use($applicant,$actor){$applicant=PmbmApplicant::lockForUpdate()->findOrFail($applicant->id);if($applicant->student)return $applicant->student;if($applicant->status!==ApplicantStatus::Registered)throw ValidationException::withMessages(['status'=>'Calon murid harus menyelesaikan daftar ulang sebelum dikonversi.']);if($applicant->nik&&Student::where('nik',$applicant->nik)->exists())throw ValidationException::withMessages(['nik'=>'NIK sudah terdaftar sebagai siswa.']);if($applicant->nisn&&Student::where('nisn',$applicant->nisn)->exists())throw ValidationException::withMessages(['nisn'=>'NISN sudah terdaftar sebagai siswa.']);$d=$applicant->data;$student=Student::create(['full_name'=>$applicant->full_name,'nisn'=>$applicant->nisn,'nik'=>$applicant->nik,'birth_place'=>$applicant->birth_place,'birth_date'=>$applicant->birth_date,'gender'=>$applicant->gender,'address'=>collect([$d['address_street']??null, isset($d['rt'],$d['rw']) ? 'RT '.$d['rt'].'/RW '.$d['rw'] : null, $d['hamlet']??null, $d['village']??null, $d['district']??null, $d['city']??null, $d['province']??null, $d['postal_code']??null])->filter()->join(', '),'phone'=>$applicant->parent_phone,'special_needs'=>$d['special_needs']??null,'father_name'=>$d['father_name']??null,'mother_name'=>$d['mother_name']??null,'guardian_name'=>$d['guardian_name']??null,'status'=>'active','created_by'=>$actor->id,'updated_by'=>$actor->id]);$applicant->update(['student_id'=>$student->id,'enrolled_by'=>$actor->id,'enrolled_at'=>now()]);$this->transition($applicant,ApplicantStatus::Enrolled,$actor,'Dikonversi menjadi siswa.');activity('pmbm')->causedBy($actor)->performedOn($applicant)->log('Calon murid dikonversi menjadi siswa.');return $student;});}
}
