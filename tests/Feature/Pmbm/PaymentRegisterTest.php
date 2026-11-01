<?php

declare(strict_types=1);

namespace Tests\Feature\Pmbm;

use App\Enums\Pmbm\{ApplicantStatus, PaymentStage, PaymentStatus};
use App\Http\Requests\Pmbm\PaymentIndexRequest;
use App\Models\AcademicYear;
use App\Models\Pmbm\{PmbmApplicant, PmbmPayment};
use App\Services\Pmbm\PaymentRegisterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PaymentRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_filters_and_separates_verified_receipts_from_pending_amounts(): void
    {
        $year = AcademicYear::create(['name' => '2026/2027', 'starts_at' => '2026-07-01', 'ends_at' => '2027-06-30', 'is_active' => true]);
        $applicant = PmbmApplicant::create(['academic_year_id' => $year->id, 'registration_number' => 'SPMB-TEST-1', 'registration_source' => 'online', 'status' => ApplicantStatus::InitialPaymentPending, 'full_name' => 'Ahmad', 'gender' => 'male', 'birth_date' => '2020-01-01', 'data' => [], 'public_token' => str_repeat('a', 64)]);
        foreach ([['verified', 100000, '2026-10-01'], ['pending', 50000, '2026-10-02'], ['rejected', 25000, '2026-09-30']] as [$status, $amount, $date]) {
            PmbmPayment::create(['applicant_id' => $applicant->id, 'payment_stage' => PaymentStage::Initial, 'amount' => $amount, 'payment_method' => 'transfer', 'reference_number' => 'BANK-TEST', 'paid_at' => $date, 'status' => PaymentStatus::from($status)]);
        }

        $register = app(PaymentRegisterService::class);
        $data = $register->data(['search' => 'Ahmad', 'academic_year_id' => $year->id, 'from' => '2026-10-01', 'until' => '2026-10-02']);
        $this->assertSame(2, $data['payments']->total());
        $this->assertEquals(100000, $data['summary']['verified']['amount']);
        $this->assertEquals(50000, $data['summary']['pending']['amount']);
        $this->assertSame(0, $data['summary']['rejected']['count']);
        $this->assertSame(PaymentStatus::Pending, $data['payments']->first()->status);
        $this->assertSame(1, $register->data(['search' => 'BANK-TEST', 'status' => 'pending', 'payment_stage' => 'initial'])['payments']->total());
        $this->assertSame(0, $register->data(['search' => 'Tidak ditemukan'])['payments']->total());
    }

    public function test_register_rejects_unknown_status_and_reversed_dates(): void
    {
        $validator = Validator::make(['status' => 'paid', 'from' => '2026-10-02', 'until' => '2026-10-01'], (new PaymentIndexRequest)->rules());
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('status'));
        $this->assertTrue($validator->errors()->has('until'));
    }

    public function test_layout_receives_page_title_and_breadcrumbs(): void
    {
        $component = new \App\View\Components\Layouts\App('Pembayaran SPMB', ['SPMB', 'Pembayaran']);
        $this->assertSame('Pembayaran SPMB', $component->title);
        $this->assertSame(['SPMB', 'Pembayaran'], $component->breadcrumbs);
    }
}
