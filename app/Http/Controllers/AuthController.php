<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
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

        $credentials = ['email' => $data['email'], 'password' => $data['password']];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau kata sandi salah.'])->onlyInput('email', 'role');
        }

        // Peran yang dipilih pada tab harus sesuai dengan peran akun.
        if (Auth::user()->role !== $data['role']) {
            $label = $data['role'] === 'pengelola' ? 'Pengelola' : 'Mitra / Pemilik';
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => "Akun ini tidak terdaftar sebagai {$label}."])->onlyInput('email', 'role');
        }

        $request->session()->regenerate();

        return $this->redirectByRole();
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /** Pengelola -> dashboard operasional, Mitra -> panel laporan (Laba Rugi). */
    public function redirectByRole(): RedirectResponse
    {
        return Auth::user()->isPengelola()
            ? redirect()->route('dashboard')
            : redirect()->route('finance.index');
    }
}
