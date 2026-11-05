<?php

namespace App\Http\Controllers;

use App\Models\Pond;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PondController extends Controller
{
    public function index()
    {
        return view('ponds.index', [
            'ponds' => Pond::withCount(['waterQualities', 'feedTransactions', 'financialTransactions'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $pond = Pond::create($request->validate($this->rules(), $this->messages()));

        return redirect()->route('ponds.index')->with('success', "{$pond->name} ditambahkan.");
    }

    public function edit(Pond $pond)
    {
        return view('ponds.edit', ['pond' => $pond]);
    }

    public function update(Request $request, Pond $pond)
    {
        $pond->update($request->validate($this->rules($pond), $this->messages()));

        return redirect()->route('ponds.index')->with('success', "{$pond->name} diperbarui.");
    }

    public function destroy(Pond $pond)
    {
        if ($pond->waterQualities()->exists() || $pond->feedTransactions()->exists() || $pond->financialTransactions()->exists()) {
            return redirect()->route('ponds.index')->with('alert', "{$pond->name} sudah memiliki data pengukuran/transaksi sehingga tidak dapat dihapus. Ubah statusnya menjadi Panen bila kolam tidak dipakai lagi.");
        }

        $pond->delete();

        return redirect()->route('ponds.index')->with('success', "{$pond->name} dihapus.");
    }

    private function rules(?Pond $pond = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('ponds', 'name')->ignore($pond?->id)],
            'fish_type' => ['required', 'in:Nila,Lele'],
            'status' => ['required', 'in:aktif,panen'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Nama kolam wajib diisi.',
            'name.unique' => 'Nama kolam sudah dipakai.',
            'fish_type.in' => 'Jenis ikan harus Nila atau Lele.',
        ];
    }
}
