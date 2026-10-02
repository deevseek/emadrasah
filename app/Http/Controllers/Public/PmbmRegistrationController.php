<?php

declare(strict_types=1);
namespace App\Http\Controllers\Public;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pmbm\StoreApplicantRequest;
use App\Models\AcademicYear;
use App\Models\Pmbm\{PmbmApplicant, PmbmDocumentRequirement};
use App\Services\Pmbm\{PmbmDocumentService, PmbmPaymentService, PmbmPublicInformationService, PmbmRegistrationService};
use Illuminate\Http\{RedirectResponse, Request, Response};
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
class PmbmRegistrationController extends Controller {
 public function index(PmbmPublicInformationService $information): View { $settings=$information->activeSetting(); return view('pmbm.public.index',['settings'=>$settings,'state'=>$information->registrationState($settings),'statusLabel'=>$information->statusLabel($settings),'fees'=>$information->fees($settings),'schedules'=>$information->schedules($settings)]); }
 public function create(PmbmPublicInformationService $information): View { $settings=$information->activeSetting(); abort_unless($information->registrationState($settings)==='open', 403, $information->statusLabel($settings)); return view('pmbm.public.form',['years'=>AcademicYear::whereKey($settings?->academic_year_id)->get(),'requirements'=>PmbmDocumentRequirement::where('is_active',true)->orderBy('sort_order')->get(),'settings'=>$settings]); }
 public function store(StoreApplicantRequest $request, PmbmRegistrationService $service): RedirectResponse { $applicant=$service->submit($request->validated(), 'online'); return redirect(URL::temporarySignedRoute('pmbm.public.success', now()->addHours(24), ['number'=>$applicant->registration_number])); }
 public function success(string $number, PmbmPaymentService $payments): View { $applicant=PmbmApplicant::where('registration_number',$number)->firstOrFail(); return view('pmbm.public.success', ['applicant'=>$applicant,'paymentSummary'=>$payments->summary($applicant)]); }
 public function receipt(string $number, PmbmDocumentService $documents): Response { $applicant=PmbmApplicant::where('registration_number',$number)->firstOrFail(); return response($documents->receipt($applicant),200,['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="tanda-terima-'.$applicant->registration_number.'.pdf"']); }
 public function statusForm(): View { return view('pmbm.public.status'); }
 public function status(Request $request, string $number, PmbmPaymentService $payments): View { $data=$request->validate(['birth_date'=>'required|date']); $applicant=PmbmApplicant::where('registration_number',$number)->whereDate('birth_date',$data['birth_date'])->firstOrFail(); return view('pmbm.public.status', ['applicant'=>$applicant,'paymentSummary'=>$payments->summary($applicant)]); }
}
