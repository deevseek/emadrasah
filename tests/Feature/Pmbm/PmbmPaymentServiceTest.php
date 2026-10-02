<?php

declare(strict_types=1);

namespace Tests\Feature\Pmbm;

use App\Enums\Pmbm\{ApplicantStatus, PaymentStage, PaymentStatus};
use App\Models\AcademicYear;
use App\Models\Pmbm\{PmbmApplicant, PmbmPayment, PmbmSetting};
use App\Services\Pmbm\PmbmPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PmbmPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_calculates_gender_fee_configured_minimum_and_verified_balance(): void
    {
        $year=AcademicYear::create(['name'=>'2026/2027','starts_at'=>'2026-07-01','ends_at'=>'2027-06-30','is_active'=>true]);
        PmbmSetting::create(['academic_year_id'=>$year->id,'minimum_initial_payment_percent'=>60,'fee_items'=>[['name'=>'Pendaftaran','male'=>100000,'female'=>120000],['name'=>'Seragam','male'=>200000,'female'=>230000]]]);
        $applicant=PmbmApplicant::create(['academic_year_id'=>$year->id,'registration_number'=>'PMBM-TEST-1','registration_source'=>'online','status'=>ApplicantStatus::InitialPaymentPending,'full_name'=>'Ahmad','gender'=>'male','birth_date'=>'2020-01-01','data'=>[],'public_token'=>str_repeat('a',64)]);
        PmbmPayment::create(['applicant_id'=>$applicant->id,'payment_stage'=>PaymentStage::Initial,'amount'=>180000,'payment_method'=>'transfer','paid_at'=>now(),'status'=>PaymentStatus::Verified]);

        $summary=app(PmbmPaymentService::class)->summary($applicant);
        $this->assertSame(300000.0,$summary['total']);
        $this->assertSame(180000.0,$summary['minimum_initial']);
        $this->assertTrue($summary['initial_satisfied']);
        $this->assertSame(120000.0,$summary['remaining']);
    }
}
