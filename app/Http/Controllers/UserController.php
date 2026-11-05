<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index', ['users' => User::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules() + [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], $this->messages());

        $user = User::create($data);

        return redirect()->route('users.index')->with('success', "Akun {$user->name} dibuat.");
    }

    public function edit(User $user)
    {
        return view('users.edit', ['user' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate($this->rules($user) + [
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], $this->messages());

        if ($user->role === 'pengelola' && $data['role'] !== 'pengelola'
            && ($user->id === $request->user()->id || User::where('role', 'pengelola')->count() <= 1)) {
            return back()->withInput()->withErrors(['role' => 'Peran pengelola tidak dapat diubah: harus tersisa minimal satu pengelola dan Anda tidak bisa menurunkan peran sendiri.']);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', "Akun {$user->name} diperbarui.");
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return redirect()->route('users.index')->with('alert', 'Anda tidak dapat menghapus akun yang sedang dipakai.');
        }

        if ($user->role === 'pengelola' && User::where('role', 'pengelola')->count() <= 1) {
            return redirect()->route('users.index')->with('alert', 'Harus tersisa minimal satu pengelola.');
        }

        if ($user->feedTransactions()->exists() || $user->waterQualities()->exists() || $user->financialTransactions()->exists()) {
            return redirect()->route('users.index')->with('alert', "{$user->name} sudah mencatat data sehingga tidak dapat dihapus agar riwayat tetap utuh.");
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Akun dihapus.');
    }

    private function rules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', 'in:pengelola,mitra_pemilik'],
        ];
    }

    private function messages(): array
    {
        return [
            'email.unique' => 'Email sudah terdaftar.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ];
    }
}
