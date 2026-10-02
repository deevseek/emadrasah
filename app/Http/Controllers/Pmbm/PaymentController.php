<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pmbm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pmbm\StorePaymentRequest;
use App\Models\Pmbm\{PmbmApplicant, PmbmPayment};
use App\Services\Pmbm\PmbmPaymentService;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View { return view('pmbm.admin.payments', ['payments'=>PmbmPayment::with(['applicant.academicYear','receiver','verifier'])->latest()->paginate(30)]); }
    public function store(StorePaymentRequest $request, PmbmApplicant $applicant, PmbmPaymentService $service): RedirectResponse
    {
        $service->record($applicant, $request->validated(), $request->user());
        return back()->with('status', 'Pembayaran berhasil dicatat dan menunggu verifikasi.');
    }
    public function verify(Request $request, PmbmApplicant $applicant, PmbmPayment $payment, PmbmPaymentService $service): RedirectResponse
    {
        $data = $request->validate(['decision' => 'required|in:verified,rejected', 'verification_note' => 'nullable|string|max:1000']);
        $service->verify($applicant, $payment, $data['decision'] === 'verified', $data['verification_note'] ?? null, $request->user());
        return back()->with('status', 'Verifikasi pembayaran berhasil disimpan.');
    }
    public function proof(PmbmApplicant $applicant, PmbmPayment $payment): StreamedResponse
    {
        abort_unless($payment->applicant_id === $applicant->id && $payment->proof_path, 404);
        return Storage::disk('local')->download($payment->proof_path, 'bukti-pembayaran-'.pathinfo($payment->proof_path, PATHINFO_BASENAME));
    }
}
