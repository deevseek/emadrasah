<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pmbm\StorePaymentRequest;
use App\Models\Pmbm\PmbmApplicant;
use App\Services\Pmbm\PmbmPaymentService;
use Illuminate\Http\RedirectResponse;

class PmbmPaymentController extends Controller
{
    public function store(StorePaymentRequest $request, string $token, PmbmPaymentService $service): RedirectResponse
    {
        $applicant = PmbmApplicant::query()->where('public_token', $token)->firstOrFail();
        $data = $request->validated();
        $data['payment_stage'] = 'initial';
        $service->record($applicant, $data);

        return back()->with('status', 'Bukti pembayaran berhasil dikirim dan akan diverifikasi panitia.');
    }
}
