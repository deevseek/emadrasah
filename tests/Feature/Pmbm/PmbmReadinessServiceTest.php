<?php

declare(strict_types=1);

namespace Tests\Feature\Pmbm;

use App\Enums\Pmbm\{ApplicantStatus, PaymentStage};
use App\Models\{AcademicYear, User};
use App\Models\Pmbm\{PmbmApplicant, PmbmApplicantDocument, PmbmDocumentRequirement, PmbmSetting};
use App\Services\Pmbm\{PmbmPaymentService, PmbmRegistrationService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PmbmReadinessServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_verified_before_documents_is_reconciled_without_repayment(): void
    {
        [$applicant,$user]=$this->scenario();
        $payment=app(PmbmPaymentService::class)->record($applicant,['payment_stage'=>'initial','amount'=>50000,'payment_method'=>'bank_transfer','paid_at'=>now()],$user);
        app(PmbmPaymentService::class)->verify($applicant,$payment,true,null,$user);
        $this->assertSame(ApplicantStatus::DocumentVerification,$applicant->fresh()->status);
        $document=$this->document($applicant);
        app(PmbmRegistrationService::class)->verifyDocument($applicant->fresh(),$document,'valid',null,$user);
        app(PmbmRegistrationService::class)->finalizeVerification($applicant->fresh(),$user);
        $this->assertSame(ApplicantStatus::DecisionPending,$applicant->fresh()->status);
        $this->assertCount(1,$applicant->payments);
    }

    public function test_documents_verified_before_partial_installments_advance_when_minimum_reached(): void
    {
        [$applicant,$user]=$this->scenario(['require_fitting'=>true]);
        $document=$this->document($applicant);
        app(PmbmRegistrationService::class)->verifyDocument($applicant,$document,'valid',null,$user);
        app(PmbmRegistrationService::class)->finalizeVerification($applicant->fresh(),$user);
        foreach([20000,30000] as $amount){$payment=app(PmbmPaymentService::class)->record($applicant->fresh(),['payment_stage'=>PaymentStage::Initial->value,'amount'=>$amount,'payment_method'=>'bank_transfer','paid_at'=>now()],$user);app(PmbmPaymentService::class)->verify($applicant->fresh(),$payment,true,null,$user);}
        $this->assertSame(ApplicantStatus::InitialPaymentVerified,$applicant->fresh()->status);
    }

    private function scenario(array $setting=[]):array
    {
        $year=AcademicYear::create(['name'=>'2027/2028','starts_at'=>'2027-07-01','ends_at'=>'2028-06-30','is_active'=>false]);
        PmbmSetting::create($setting+['academic_year_id'=>$year->id,'enabled'=>true,'minimum_initial_payment_percent'=>50,'fee_items'=>[['name'=>'Biaya','male'=>100000,'female'=>100000]],'require_child_interview'=>false]);
        PmbmDocumentRequirement::query()->update(['is_active'=>false]);
        PmbmDocumentRequirement::create(['name'=>'Akta','code'=>'akta_test','is_required'=>true,'is_active'=>true,'sort_order'=>1,'channel'=>'both']);
        $applicant=PmbmApplicant::create(['academic_year_id'=>$year->id,'registration_number'=>'TEST-1','registration_source'=>'online','status'=>ApplicantStatus::Submitted,'full_name'=>'Anak Uji','gender'=>'male','birth_date'=>'2020-01-01','data'=>[],'public_token'=>str_repeat('b',64)]);
        return [$applicant,User::factory()->create()];
    }

    private function document(PmbmApplicant $applicant):PmbmApplicantDocument
    {
        return PmbmApplicantDocument::create(['applicant_id'=>$applicant->id,'document_type'=>'akta_test','original_name'=>'akta.pdf','path'=>'pmbm/test/akta.pdf','status'=>'uploaded','is_current'=>true,'version'=>1]);
    }
}
