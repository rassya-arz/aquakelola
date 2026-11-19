<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 60;

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'role' => ['required', 'in:pengelola,mitra_pemilik'],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        // Kunci per IP agar mengganti email atau me-refresh halaman tidak melewati pembatasan.
        $key = 'login:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            return back()
                ->withErrors(['email' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$seconds} detik."])
                ->onlyInput('email', 'role');
        }

        // Peran ikut menjadi syarat kredensial, jadi akun dengan peran lain tidak pernah sempat login.
        $credentials = [
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
        ];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            return back()
                ->withErrors(['email' => 'Email, kata sandi, atau peran yang dipilih tidak sesuai.'])
                ->onlyInput('email', 'role');
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        // Kedua peran (pengelola & mitra/pemilik) memiliki akses ke Dashboard.
        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
