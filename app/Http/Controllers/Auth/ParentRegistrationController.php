<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\GuardianRelationship;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterParentRequest;
use App\Services\ParentPortal\RegisterGuardianService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ParentRegistrationController extends Controller
{
    public function create(): View
    {
        return view('auth.parent-register', ['relationships' => GuardianRelationship::cases()]);
    }

    public function store(RegisterParentRequest $request, RegisterGuardianService $registrations): RedirectResponse
    {
        $registrations->register($request->validated());

        return redirect()->route('parent.login')->with(
            'status',
            'Pendaftaran berhasil. Akun telah terhubung dengan anak dan konfirmasi telah dikirim ke email Anda.',
        );
    }
}
