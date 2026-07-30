<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PasswordUpdateController extends Controller
{
    public function edit(): View
    {
        return view('auth.change-password');
    }

    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        $request->user()->update([...$request->safe()->only('password'), 'must_change_password' => false]);

        Auth::logoutOtherDevices($request->string('password')->toString());

        return redirect()->route('dashboard')->with('status', 'Password berhasil diperbarui.');
    }
}
