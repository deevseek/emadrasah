<?php

declare(strict_types=1);
namespace App\Http\Controllers\Public;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pmbm\StoreApplicantRequest;
use App\Models\AcademicYear;
use App\Models\Pmbm\{PmbmApplicant, PmbmDocumentRequirement};
use App\Services\Pmbm\{PmbmDocumentService, PmbmPaymentService, PmbmPublicInformationService, PmbmPublicStatusPresenter, PmbmRegistrationService};
use Illuminate\Http\{RedirectResponse, Request, Response};
use Illuminate\Support\Facades\URL;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
class PmbmRegistrationController extends Controller {
 public function index(PmbmPublicInformationService $information): View { $settings=$information->activeSetting(); return view('pmbm.public.index',['settings'=>$settings,'state'=>$information->registrationState($settings),'statusLabel'=>$information->statusLabel($settings),'fees'=>$information->fees($settings),'schedules'=>$information->schedules($settings)]); }
 public function create(PmbmPublicInformationService $information): View { $settings=$information->activeSetting(); abort_unless($information->registrationState($settings)==='open', 403, $information->statusLabel($settings)); return view('pmbm.public.form',['years'=>AcademicYear::whereKey($settings?->academic_year_id)->get(),'requirements'=>PmbmDocumentRequirement::where('is_active',true)->whereIn('channel',['online','both'])->where(fn($q)=>$q->where('setting_id',$settings?->id)->orWhereNull('setting_id'))->orderBy('sort_order')->get(),'settings'=>$settings]); }
 public function store(StoreApplicantRequest $request, PmbmRegistrationService $service): RedirectResponse { $applicant=$service->submit($request->validated(), 'online'); return redirect(URL::temporarySignedRoute('pmbm.public.success', now()->addHours(24), ['number'=>$applicant->registration_number])); }
 public function success(string $number, PmbmPaymentService $payments): View { $applicant=PmbmApplicant::where('registration_number',$number)->firstOrFail(); return view('pmbm.public.success', ['applicant'=>$applicant,'paymentSummary'=>$payments->summary($applicant),'setting'=>\App\Models\Pmbm\PmbmSetting::where('academic_year_id',$applicant->academic_year_id)->firstOrFail()]); }
 public function receipt(string $number, PmbmDocumentService $documents): Response { $applicant=PmbmApplicant::where('registration_number',$number)->firstOrFail(); return response($documents->receipt($applicant),200,['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="tanda-terima-'.$applicant->registration_number.'.pdf"']); }
 public function acceptance(string $token,PmbmDocumentService $documents):Response{$applicant=PmbmApplicant::where('public_token',$token)->firstOrFail();abort_unless($applicant->decision?->published_at&&$applicant->decision?->decision==='accepted',404);return response($documents->acceptanceLetter($applicant),200,['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="surat-penerimaan-'.$applicant->registration_number.'.pdf"']);}
 public function replaceDocument(Request $request,string $token,string $type,PmbmRegistrationService $service):RedirectResponse{$applicant=PmbmApplicant::where('public_token',$token)->firstOrFail();$current=$applicant->currentDocuments()->where('document_type',$type)->firstOrFail();abort_unless($current->status==='revision_required',403);$data=$request->validate(['document'=>'required|file|mimes:jpg,jpeg,png,pdf|max:5120']);$service->replaceDocument($applicant,$type,$data['document']);app(\App\Services\Pmbm\PmbmReadinessService::class)->reconcile($applicant);return back()->with('status','Dokumen pengganti berhasil dikirim dan menunggu verifikasi.');}
 public function statusForm(): View { return view('pmbm.public.status'); }
 public function status(Request $request, string $number, PmbmPaymentService $payments,PmbmPublicStatusPresenter $presenter,\App\Services\Pmbm\PmbmWorkflowService $workflow): View { $data=$request->validate(['birth_date'=>'required|date']); $applicant=PmbmApplicant::with(['decision','currentDocuments'])->where('registration_number',$number)->whereDate('birth_date',$data['birth_date'])->firstOrFail();$setting=\App\Models\Pmbm\PmbmSetting::where('academic_year_id',$applicant->academic_year_id)->first();if($applicant->decision?->finalized_at&&!$applicant->decision?->published_at&&$setting?->announcement_at?->isPast()){$workflow->publishDecision($applicant);$applicant->refresh()->load(['decision','currentDocuments']);}$summary=$payments->summary($applicant); return view('pmbm.public.status', ['applicant'=>$applicant,'paymentSummary'=>$summary,'publicStatus'=>$presenter->present($applicant,$summary),'setting'=>$setting]); }
}
