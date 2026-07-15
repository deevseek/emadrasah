<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $key = 'login:'.$request->ip().':'.strtolower($credentials['email']);
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Terlalu banyak percobaan login. Silakan coba beberapa menit lagi.']);
        }
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            $this->record($request, null, false, 'Kredensial tidak sesuai.');
            throw ValidationException::withMessages(['email' => 'Email atau password tidak sesuai.']);
        }
        if (! $request->user()->is_active) {
            $this->record($request, $request->user()->id, false, 'Akun nonaktif.');
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'Akun Anda sedang dinonaktifkan.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();
        $this->record($request, $request->user()->id, true);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function record(Request $request, ?int $userId, bool $successful, ?string $reason = null): void
    {
        LoginHistory::create(['user_id' => $userId, 'email' => (string) $request->input('email'), 'ip_address' => $request->ip(), 'user_agent' => (string) $request->userAgent(), 'successful' => $successful, 'failure_reason' => $reason, 'attempted_at' => now()]);
    }
}
