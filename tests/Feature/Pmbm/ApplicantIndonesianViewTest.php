<?php

declare(strict_types=1);

namespace Tests\Feature\Pmbm;

use App\Enums\Pmbm\ApplicantStatus;
use App\Models\Pmbm\PmbmApplicant;
use App\Models\Pmbm\PmbmApplicantDocument;
use App\Models\Pmbm\PmbmPayment;
use App\Models\Pmbm\PmbmStatusHistory;
use App\Models\User;
use App\Services\Pmbm\PmbmApplicantPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class ApplicantIndonesianViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_displays_indonesian_labels_values_dates_and_history_even_with_english_locale(): void
    {
        $this->app->setLocale('en');
        $this->withoutVite();
        view()->share('errors', new ViewErrorBag);
        $this->actingAs(User::factory()->create());
        Gate::before(fn () => true);
        $applicant = new PmbmApplicant([
            'id' => 1, 'full_name' => 'Nama Calon Murid', 'registration_number' => 'SPMB-001',
            'status' => ApplicantStatus::Submitted, 'gender' => 'male', 'birth_date' => '2020-08-17',
            'data' => ['father_status' => 'alive', 'mother_domicile' => 'domestic',
                'father_job' => 'pegawai_swasta', 'father_income' => '500k_1jt',
                'guardian_type' => 'mother', 'special_needs' => 'none',
                'health_notes' => 'Catatan privat'],
        ]);
        foreach (['fitting', 'interview', 'childInterview', 'observation', 'decision'] as $relation) {
            $applicant->setRelation($relation, null);
        }
        $document = new PmbmApplicantDocument(['id' => 1, 'document_type' => 'akta_kelahiran', 'status' => 'uploaded', 'version' => 1]);
        $document->setRelation('verifiedBy', null);
        $applicant->setRelation('documents', collect([$document]));
        $payment = new PmbmPayment(['payment_stage' => 'initial', 'status' => 'pending', 'amount' => 50000, 'payment_method' => 'bank_transfer']);
        $payment->id = 1;
        $payment->setRelation('receiver', null)->setRelation('verifier', null);
        $applicant->setRelation('payments', collect([$payment]));
        $applicant->setRelation('histories', collect([new PmbmStatusHistory(['to_status' => 'submitted'])]));

        $view = $this->view('pmbm.admin.show', [
            'applicant' => $applicant, 'sensitive' => false, 'questions' => collect(),
            'documentNames' => ['akta_kelahiran' => 'Akta Kelahiran'],
            'paymentSummary' => ['total' => 100000, 'minimum_percent' => 50, 'minimum_initial' => 50000, 'verified' => 0, 'remaining' => 100000],
        ]);
        foreach (['Nama lengkap', 'Jenis kelamin', 'Laki-laki', '17 Agustus 2020', 'Masih hidup',
            'Dalam negeri', 'Pegawai Swasta', 'Rp500.000', 'Ibu kandung', 'Tidak ada',
            'Akta Kelahiran', 'Sudah diunggah', 'Pembayaran awal', 'Transfer bank', 'Pendaftaran diterima'] as $text) {
            $view->assertSeeText($text);
        }
        foreach (['Full Name', 'Birth Date', 'bank_transfer', 'Catatan privat'] as $text) {
            $view->assertDontSeeText($text);
        }
    }

    public function test_presenter_preserves_free_text_and_handles_empty_and_legacy_values(): void
    {
        $presenter = new PmbmApplicantPresenter;
        $this->assertSame('—', $presenter->value('father_job', null));
        $this->assertSame('Pekerjaan khusus keluarga', $presenter->value('father_job', 'Pekerjaan khusus keluarga'));
        $this->assertSame('0', $presenter->value('sibling_count', 0));
        $this->assertSame('Pembayaran daftar ulang', $presenter->value('payment_stage', 'registration'));
        $this->assertSame('Memerlukan pendampingan', $presenter->value('observation', 'NEEDS_SUPPORT'));
        $this->assertSame('Hadir', $presenter->value('attendance', 'present'));
        $this->assertSame('Daftar cadangan', $presenter->value('decision', 'waitlisted'));
        $this->assertSame('Formulir pendaftaran luring', $presenter->documentName('offline_registration_form', []));
        $this->assertSame('Status belum tersedia', $presenter->status('legacy_status'));
        $this->assertSame('Pengukuran seragam selesai', $presenter->status('fitting_completed'));
    }
}
