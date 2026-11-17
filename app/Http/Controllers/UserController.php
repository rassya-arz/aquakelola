<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Manajemen akun: hanya dapat diakses peran Mitra / Pemilik (dijaga di routes/web.php). */
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

        $roleChanged = $data['role'] !== $user->role;

        if ($roleChanged && $user->id === $request->user()->id) {
            return back()->withInput()->withErrors(['role' => 'Anda tidak dapat mengubah peran akun yang sedang dipakai.']);
        }

        if ($roleChanged && $this->isLastMitra($user)) {
            return back()->withInput()->withErrors(['role' => 'Harus tersisa minimal satu akun Mitra / Pemilik.']);
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

        if ($this->isLastMitra($user)) {
            return redirect()->route('users.index')->with('alert', 'Akun Mitra / Pemilik terakhir tidak dapat dihapus.');
        }

        if ($user->feedTransactions()->exists() || $user->waterQualities()->exists() || $user->financialTransactions()->exists()) {
            return redirect()->route('users.index')->with('alert', "{$user->name} sudah mencatat data sehingga tidak dapat dihapus agar riwayat tetap utuh.");
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Akun dihapus.');
    }

    /** Akun ini satu-satunya Mitra / Pemilik, sehingga peran maupun keberadaannya harus dijaga. */
    private function isLastMitra(User $user): bool
    {
        return $user->isMitraPemilik() && User::where('role', User::ROLE_MITRA_PEMILIK)->count() <= 1;
    }

    private function rules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::in([User::ROLE_PENGELOLA, User::ROLE_MITRA_PEMILIK])],
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
