<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pmbm\StorePaymentRequest;
use App\Models\Pmbm\PmbmApplicant;
use App\Services\Pmbm\PmbmPaymentService;
use Illuminate\Http\RedirectResponse;
use App\Models\Pmbm\PmbmSetting;
use Illuminate\Validation\ValidationException;

class PmbmPaymentController extends Controller
{
    public function store(StorePaymentRequest $request, string $token, PmbmPaymentService $service): RedirectResponse
    {
        $applicant = PmbmApplicant::query()->where('public_token', $token)->firstOrFail();
        $data = $request->validated();
        $data['payment_stage'] = 'initial';
        $setting=PmbmSetting::where('academic_year_id',$applicant->academic_year_id)->firstOrFail();
        if(!in_array($data['payment_method'],$setting->allowed_payment_methods??['bank_transfer'],true))throw ValidationException::withMessages(['payment_method'=>'Metode pembayaran tidak tersedia.']);
        if($setting->payment_proof_required&&!$request->hasFile('proof'))throw ValidationException::withMessages(['proof'=>'Bukti pembayaran wajib diunggah.']);
        $service->record($applicant, $data);

        return back()->with('status', 'Bukti pembayaran berhasil dikirim dan akan diverifikasi panitia.');
    }
}
